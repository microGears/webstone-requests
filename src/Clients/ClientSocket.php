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
 * @datetime 07.08.2024 14:29:00
 */
class ClientSocket extends RequestClientAbstract
{
    public function isSupported():bool
    {
        return function_exists( 'fsockopen' );
    }

    public function request(string $url, string $method = 'GET', array $headers = [],mixed $content = null): Response
    {
        $response = new Response();

        try {
            if (!$url_parts = UrlHelper::parse( $url )) {
                throw new EHttpException( 501, sprintf( 'Malformed URL: %s', $url ) );
            }

            $fsockopen_host = $url_parts[2];

            if (!isset( $url_parts[3] ) || empty( $url_parts[3] )) {
                if (($url_parts[1] == 'ssl' || $url_parts[1] == 'https') && extension_loaded( 'openssl' )) {
                    $fsockopen_host = "ssl://$fsockopen_host";
                    $url_parts[3]    = 443;
                } else {
                    $url_parts[3] = 80;
                }
            }

            if (strtolower( $fsockopen_host ) === 'localhost') {
                $fsockopen_host = '127.0.0.1';
            }

            $err     = 0;
            $err_text = '';
            if (($handle = fsockopen( $fsockopen_host, (int)$url_parts[3], $err, $err_text, $this->getTimeout() )) === false) {
                throw new EHttpException( 501, $err_text );
            }

            $request_path = $url_parts[1].'://'.$url_parts[2].$url_parts[4].(isset( $url_parts[5] ) && !empty( $url_parts[5] ) ? $url_parts[5] : '').(isset( $url_parts[6] ) ? '?'.$url_parts[6] : '');

            if (empty( $request_path )) {
                $request_path = '/';
            }

            $headers = is_array( $headers ) ? $headers : [];
            $headers = array_merge( $headers, $this->getHeaders() );

            $content = $content !== null ? $content : $this->getContent();
            $content = (is_array( $content ) || is_object( $content )) ? http_build_query( $content, '', '&' ) : $content;

            if (!is_null( $content )) {
                $headers['Content-Type']   = 'application/x-www-form-urlencoded; charset=utf-8';
                $headers['Content-Length'] = strlen( $content );
            }

            if ($url_parts[3] == 443) {
                $headers['Host'] = $url_parts[2].':'.$url_parts[3];
            } else {
                $headers['Host'] = $url_parts[2];
            }


            if ($authType = $this->getAuthType()) {
                $headers['Authorization'] = sprintf( '%s %s', $authType, base64_encode( $this->getUsername().":".$this->getPassword() ) );
            }

            $headers['Connection'] = 'Close';

            $output = sprintf( "%s %s HTTP/%.1f\r\n", $method !== null ? $method : $this->getMethod(), $request_path, $this->getProtocolVersion() );;
            if (is_array( $headers )) {
                $output .= implode( "\r\n", self::flatten( $headers ) );
            }

            if (!is_null( $content )) {
                $output .= "\r\n$content";
            }

            fwrite( $handle, $output );
            stream_set_timeout( $handle, $this->getTimeout() );

            if ($this->isBlocking() == false) {
                fclose( $handle );
                $response->setStatusCode( 200 );

                return $response;
            }

            $contents = "";
            while (!feof( $handle )) {
                $contents .= fgets( $handle );
            }

            $response->setContents( $contents );
            fclose( $handle );

            if ($method != 'HEAD' && ($location = $response->getHeader( 'Location' )) !== false) {
                $redirect = $this->getRedirectsCount();
                if ($redirect-- > 0) {
                    $this->setRedirectsCount( $redirect );

                    return $this->request( $location, $method, $headers, $content );
                } else {
                    throw new EHttpException( 501, sprintf( 'Lot of redirects for %s', $location ) );
                }
            }

        } catch (\Exception $exception) {
            $response->setStatusCode( $exception->getCode() );
            $response->setStatus( sprintf( '%s caused an exception: %s', __METHOD__, $exception->getMessage() ) );
        }

        return $response;
    }
}

/* End of file ClientSocket.php */
