<?php

declare(strict_types=1);
/**
 * This file is part of WebStone\Requests.
 *
 * (C) 2009-2024 Maxim Kirichenko <kirichenko.maxim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebStone\Requests\Clients;

use WebStone\Requests\Exceptions\EHttpException;
use WebStone\Stdlib\Helpers\UrlHelper;

/**
 * @author Maxim Kirichenko <kirichenko.maxim@gmail.com>
 * @datetime 07.08.2024 12:29:00
 */
class ClientCurl extends RequestClientAbstract
{
    public function isSupported(): bool
    {
        return function_exists('curl_init') && function_exists('curl_exec');
    }

    public function request(string $url, string $method = 'GET', $headers = [], $content = null): Response
    {
        $response = new Response();

        try {
            if (!$url_parts = UrlHelper::parse($url)) {                
                throw new EHttpException(501, sprintf('Malformed URL: %s', $url));
            }

            $handle = curl_init();
            curl_setopt_array($handle, [
                CURLOPT_FRESH_CONNECT  => true,
                CURLOPT_CONNECTTIMEOUT => $timeout = ceil(max($this->getTimeout(), 1)),
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_URL            => $url,
                CURLOPT_REFERER        => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS      => max($this->getRedirectsCount(), 1),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);

            $content = $content !== null ? $content : $this->getContent();
            $content = (is_array($content) || is_object($content)) ? http_build_query($content, '', '&') : $content;

            $method = $method !== null ? strtoupper($method) : $this->getMethod();
            switch ($method) {
                case 'GET':
                    if ($content != null) {
                        curl_setopt($handle, CURLOPT_URL, sprintf("%s?%s", $url, $content));
                    }
                    break;
                case 'POST':
                    curl_setopt($handle, CURLOPT_POST, true);
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $content);
                    break;
                case 'HEAD':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
                    curl_setopt($handle, CURLOPT_NOBODY, true);
                    break;
                case 'TRACE':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
                    break;
                case 'PUT':
                case 'PATCH':
                case 'DELETE':
                case 'OPTIONS':
                default:
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
                    if ($content != null) {
                        curl_setopt($handle, CURLOPT_POSTFIELDS, $content);
                    }
            }

            $headers               = is_array($headers) ? $headers : [];
            $headers               = array_merge($headers, $this->getHeaders());
            $headers['Host']       = $url_parts[2];
            $headers['Connection'] = 'Close';

            if ($auth_type = $this->getAuthType()) {
                curl_setopt($handle, CURLOPT_HTTPAUTH, defined($type = 'CURLAUTH_' . strtoupper($auth_type)) ? constant($type) : CURLAUTH_ANY);
            }

            if (($user = $this->getUsername()) && ($password = $this->getPassword())) {
                curl_setopt($handle, CURLOPT_USERPWD, $user . ':' . $password);
            }

            // Blocking?
            curl_setopt($handle, CURLOPT_HEADER, (bool)$this->isBlocking());

            // The option doesn't work with safe mode or when open_basedir is set.
            // Disable HEAD when making HEAD requests.
            $follow_location = false;
            if (!ini_get('safe_mode') && !ini_get('open_basedir') && 'HEAD' != $method) {
                $follow_location = curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            }

            if (is_array($headers) && count($headers)) {
                curl_setopt($handle, CURLOPT_HTTPHEADER, self::flatten($headers));
            }

            switch ($this->getProtocolVersion()) {
                case '1.1':
                    curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                    break;
                case '2.0':
                    curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
                    break;
                default:
                    curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                    break;
            }

            $contents = curl_exec($handle);

            if ($this->isBlocking() == false) {
                curl_close($handle);
                $response->setStatusCode(200);

                return $response;
            }

            if (!empty($contents)) {
                $headers_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
                $response->setHeaders($headers = trim(substr($contents, 0, $headers_size)));
                if (strlen($contents) > $headers_size) {
                    $response->setContents(substr($contents, $headers_size));
                }
            }

            if ($curl_error = curl_error($handle)) {
                throw new EHttpException(500, $curl_error);
            }

            $response->setStatusCode(curl_getinfo($handle, CURLINFO_HTTP_CODE));
            curl_close($handle);


            if ($follow_location !== true && ($location = $response->getHeader('Location')) !== false) {
                $redirect = $this->getRedirectsCount();
                if ($redirect-- > 0) {
                    $this->setRedirectsCount($redirect);

                    return $this->request($location, $method, $headers, $content);
                } else {
                    throw new EHttpException(501, sprintf('Lot of redirects for %s', $location));
                }
            }
        } catch (\Exception $exception) {
            $response->setStatusCode($exception->getCode());
            $response->setStatus(sprintf('%s caused an exception: %s', __METHOD__, $exception->getMessage()));
        }

        return $response;
    }
}

/* End of file ClientCurl.php */