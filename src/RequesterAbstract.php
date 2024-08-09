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

use WebStone\Requests\Clients\ClientCurl;
use WebStone\Requests\Clients\RequestClientAbstract;
use WebStone\Requests\Clients\Response;
use WebStone\Stdlib\Classes\AutoInitialized;

abstract class RequesterAbstract extends AutoInitialized
{
    protected int $port = 80;
    protected string $host = 'localhost';
    protected string $protocol = 'http';
    protected ?RequestClientAbstract $client = null;

    /**
     * Get(build) the request URL.
     *
     * @param string $url The URL to append to the base URL.
     * @return string The complete request URL.
     */
    public function getRequestUrl(string $url = '')
    {
        $result = $this->getProtocol() . '://' . $this->getHost();

        if ((string)($port = $this->getPort()) != '80') {
            $result .= ":$port";
        }

        if (!empty($url)) {
            $result .= '/' . trim($url, '/');
        }

        return $result;
    }

    abstract public function request(string $url, string $method = 'GET', $headers = [], $content = null, $async = false): Response;

    /**
     * Retrieves the port number for the request.
     *
     * @return int The port number.
     */
    public function getPort():int
    {
        $result = (int)$this->port;
        if (empty($result) || $result < 1) {
            $result = 80;
        }

        return $result;
    }

    /**
     * Set the port value.
     *
     * @param mixed $value The value to set as the port.
     * @return self
     */
    public function setPort(mixed $value): self
    {
        $this->port = (int)$value;
        return $this;
    }

    /**
     * Retrieves the host of the request.
     *
     * @return string The host of the request.
     */
    public function getHost():string
    {
        if (empty($this->host)) {
            $this->host = 'localhost';
        }

        return $this->host;
    }

    /**
     * Set the host value.
     *
     * @param mixed $value The value to set as the host.
     * @return self
     */
    public function setHost(mixed $value): self
    {
        $this->host = (string)$value;
        return $this;
    }

    /**
     * Retrieves the protocol used for the request.
     *
     * @return string The protocol used for the request.
     */
    public function getProtocol():string
    {
        if (empty($this->protocol)) {
            $this->protocol = 'http';
        }

        return $this->protocol;
    }

    /**
     * Set the protocol for the requester.
     *
     * @param string $value The protocol value to set.
     * @return self Returns the updated instance of the requester.
     */
    public function setProtocol(string $value): self
    {
        $value = strtolower(strtr($value, ['-' => '', '_' => '', ' ' => '', '\\' => '', '/' => '', ':' => '']));
        $this->protocol = $value;

        return $this;
    }

    /**
     * Retrieves the client for making requests.
     *
     * @return RequestClientAbstract The client for making requests.
     */
    public function getClient():RequestClientAbstract
    {
        if ($this->client === null) {
            $this->client = $this->getDefaultClient();
        }

        return $this->client;
    }

    /**
     * Set the client for the requester.
     *
     * @param mixed $client The client to set.
     * @return self
     */
    public function setClient(mixed $client): self
    {
        if (!is_object($client)) {
            $client = AutoInitialized::turnInto($client);
        }

        if (!($client instanceof RequestClientAbstract)) {
            throw new \InvalidArgumentException('Invalid client instance');
        }

        $this->client = $client;
        return $this;
    }

    /**
     * Get the default client for making requests.
     *
     * @return RequestClientAbstract The default client for making requests.
     */
    protected function getDefaultClient(): RequestClientAbstract
    {
        return new ClientCurl([
            'timeout' => 30,
            'redirects' => 5,
            'blocking' => true,
            'protocol_version' => '1.1',
        ]);
    }
}
