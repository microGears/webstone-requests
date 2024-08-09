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

namespace WebStone\Requests;

use Exception;
use WebStone\Requests\Clients\Response;

class Requester extends RequesterAbstract
{
    /**
     * Sends a HTTP request to the specified URL.
     *
     * @param string $url The URL to send the request to.
     * @param string $method The HTTP method to use for the request. Default is 'GET'.
     * @param array $headers The headers to include in the request. Default is an empty array.
     * @param mixed $content The content to include in the request body. Default is null.
     * @param bool $async Whether to send the request asynchronously. Default is false.
     *
     * @return Response The response received from the server.
     */
    public function request(string $url, string $method = 'GET', $headers = [], $content = null, $async = false): Response
    {
        if ($client = $this->getClient()) {
            $client->setBlocking(!$async);
            return $client->request($this->getRequestUrl($url), $method, $headers, $content);
        }

        throw new Exception(sprintf('%s: expected a RequestClientAbstract instance; received "%s"', __METHOD__, is_object($client) ? get_class($client) : gettype($client)));
    }
}
