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

use WebStone\Requests\Constants;
use WebStone\Stdlib\Helpers\JsonHelper;
use WebStone\Stdlib\Helpers\TypeHelper;

/**
 * Response
 *
 * @author Maxim Kirichenko <kirichenko.maxim@gmail.com>
 * @datetime 07.08.2024 11:37:00
 */
class Response
{
    protected $contents        = null;
    protected array $headers   = [];
    protected string $status   = 'Unknown';
    protected int $status_code = 0;

    public function __construct(mixed $data = null)
    {
        if (is_string($data)) {
            $this->setContents($data);
        }
    }

    /**
     * Retrieves the content type of the response.
     *
     * @return string The content type of the response.
     */
    public function getContentType(): string
    {
        return $this->getHeader('Content-Type');
    }

    /**
     * Retrieves the contents of the response.
     *
     * @return mixed The contents of the response.
     */
    public function getContents(): mixed
    {
        if (JsonHelper::isJson( $this->contents )) {
            $this->contents = json_decode( $this->contents, true );
        }

        return $this->contents;
    }

    /**
     * Retrieves the value of a specific item from the response contents.
     *
     * @param mixed $key The key of the item to retrieve.
     * @param mixed $default The default value to return if the item is not found.
     * @param string|null $expected_type The expected type of the item.
     * @return mixed The value of the item, or the default value if not found.
     */
    public function getContentsItem(mixed $key, mixed $default = null, ? string $expected_type = null)
    {
        $result = $default;
        if (is_array( $array = $this->getContents() ) && array_key_exists( $key, $array )) {
            if ($expected_type !== null) {
                if (TypeHelper::isType( $array[$key], $expected_type )) {
                    $result = $array[$key];
                }
            } else {
                $result = $array[$key];
            }
        }

        return $result;
    }

    /**
     * Retrieves the value of the specified header key.
     *
     * @param string $key The key of the header to retrieve.
     * @return mixed The value of the header.
     */
    public function getHeader(string $key): mixed
    {
        if ($this->hasHeader($key)) {
            return $this->headers[$key];
        }

        return false;
    }

    /**
     * Retrieves the headers of the response.
     *
     * @return array The headers of the response.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the status of the response.
     *
     * @return string The status of the response.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Retrieves the status code of the response.
     *
     * @return int The status code of the response.
     */
    public function getStatusCode(): int
    {
        return $this->status_code;
    }

    /**
     * Checks if the response has a specific header.
     *
     * @param string $header The header to check.
     * @return bool Returns true if the response has the specified header, false otherwise.
     */
    public function hasHeader(string $header): bool
    {
        return array_key_exists($header, $this->headers);
    }

    /**
     * Set the contents of the response.
     *
     * @param mixed $data The data to set as the contents of the response.
     * @return void
     */
    public function setContents(mixed $data)
    {
        if (is_string($data) && strpos($data, 'HTTP/') !== false) {
            if (strpos($data, $delimiter = "\r\n\r\n") !== false) {
                [$headers, $contents] = explode($delimiter, $data, 2);
                $this->setHeaders($headers);
                $data = $contents;
            }
        }

        $this->contents = $data;
    }

    /**
     * Set a header value for the response.
     *
     * @param string $key The header key.
     * @param mixed $value The header value.
     * @return void
     */
    public function setHeader(string $key, mixed $value)
    {
        $value = trim($value);
        if (!empty($value)) {
            // normalize header name
            $key = ucwords(preg_replace('/[\s_-]+/', ' ', $key));
            $key = str_replace(' ', '-', $key);

            // add or update
            $this->headers[$key] = $value;
        }
    }

    /**
     * Set the headers for the response.
     *
     * @param mixed $headers The headers to be set.
     * @return void
     */
    public function setHeaders(mixed $headers)
    {
        // split headers, one per array element
        if (is_string($headers)) {

            if (strpos($headers, "\r\n\r\n") !== false) {
                $header_parts = explode("\r\n\r\n", $headers);
                $headers      = $header_parts[count($header_parts) - 1];
            }

            // tolerate line terminator: CRLF = LF (RFC 2616 19.3)
            $headers = str_replace("\r\n", "\n", $headers);
            // unfold folded header fields. LWS = [CRLF] 1*( SP | HT ) <US-ASCII SP, space (32)>, <US-ASCII HT, horizontal-tab (9)> (RFC 2616 2.2)
            $headers = preg_replace('/\n[ \t]/', ' ', $headers);
            // create the headers array
            $headers = explode("\n", $headers);
        }
        
        if (!is_array($headers)) {
            return;
        }

        foreach ($headers as $header) {
            if (empty($header)) {
                continue;
            }

            if (strpos($header, 'HTTP/') === 0) {
                [, $code, $status] = explode(' ', $header, 3);
                $this->setStatusCode($code);
                $this->setStatus($status);
                continue;
            }

            if (strpos($header, ':') !== false) {
                [$name, $value] = explode(':', $header, 2);
                $this->setHeader($name, trim($value));
            }
        }
    }

    /**
     * Set the status of the response.
     *
     * @param string $status The status text.
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the status code of the response.
     *
     * @param mixed $code The status code to set.
     * @return self
     */
    public function setStatusCode(mixed $code): self
    {
        if (!is_numeric($code)) {
            throw new \Exception(sprintf('Invalid status code: %s', $code));
        }

        $this->setStatus(Constants::$httpStatuses[$this->status_code = (int)$code] ?? 'Unknown');
        return $this;
    }
}
/** End of Response **/
