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
 * @datetime 07.08.2024 13:29:00
 */
class ClientStream extends RequestClientAbstract
{
    public function isSupported():bool
    {
        return function_exists('fopen') && ini_get('allow_url_fopen') == true;
    }

    public function request(string $url, string $method = 'GET', array $headers = [],mixed $content = null): Response
    {   
        $response = new Response();

        try {
            if (!$urlParts = UrlHelper::parse( $url )) {
                throw new EHttpException( 501, sprintf( 'Malformed URL: %s', $url ) );
            }

            if (!in_array( $urlParts[1], ['http', 'https'] )) {
                $url = str_replace( $urlParts[1], 'http', $url );
            }

            $headers = is_array( $headers ) ? $headers : [];
            $headers = array_merge( $headers, $this->getHeaders() );

            $content = $content !== null ? $content : $this->getContent();
            $content = (is_array( $content ) || is_object( $content )) ? http_build_query( $content, '', '&' ) : $content;

            if (!is_null( $content )) {
                $headers['Content-Type']   = 'application/x-www-form-urlencoded; charset=utf-8';
                $headers['Content-Length'] = strlen( $content );
            }

            $headers['Host']       = $urlParts[2];
            $headers['Connection'] = 'Close';

            $resource = stream_context_create( [
              'http' => [
                'method'           => strtoupper( $method !== null ? $method : $this->getMethod() ),
                'header'           => implode( "\r\n", self::flatten( $headers ) ),
                'content'          => $content,
                'max_redirects'    => $this->getRedirectsCount(),
                'protocol_version' => $this->getProtocolVersion(),
                'timeout'          => $this->getTimeout(),
              ],
            ] );

            $handle = fopen( $url, 'rb', false, $resource );

            if ($this->isBlocking() == false) {
                fclose( $handle );
                $response->setStatusCode( 200 );

                return $response;
            }

            $contents = "";
            while (!feof( $handle )) {
                $contents .= fread( $handle, 4096 );
            }

            $response->setContents( $contents );

            if (function_exists( 'stream_get_meta_data' )) {
                $meta = stream_get_meta_data( $handle );
                if (isset( $meta['wrapper_data'] )) {
                    $response->setHeaders( $meta['wrapper_data'] );
                }
            }

            fclose( $handle );

        } catch (\Exception $exception) {
            $response->setStatusCode( $exception->getCode() );
            $response->setStatus( sprintf( '%s caused an exception: %s', __METHOD__, $exception->getMessage() ) );
        }

        return $response;
    }
}

/* End of file ClientStream.php */
