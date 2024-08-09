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

use WebStone\Stdlib\Classes\AutoInitialized;

/**
 * RequestClientAbstract
 *
 * @author Maxim Kirichenko <kirichenko.maxim@gmail.com>
 * @datetime 07.08.2024 11:29:00
 */
abstract class RequestClientAbstract extends AutoInitialized implements RequestClientInterface
{
    protected ?string $auth_type       = null;
    protected bool $blocking           = false;
    protected $content                 = null;
    protected array $headers           = [];
    protected string $method           = 'GET';
    protected string $protocol_version = '1.0';
    protected int $redirects_count     = 5;
    protected int $timeout             = 5;
    protected ?string $username        = null;
    protected ?string $password        = null;

    protected static function flatten(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = sprintf('%s: %s', $key, $value);
        }

        return $result;
    }

    public function getAuthType(): ?string
    {
        return $this->auth_type;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocol_version;
    }

    public function getRedirectsCount(): int
    {
        return $this->redirects_count;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function isBlocking(): bool
    {
        return $this->blocking == true;
    }

    public function hasHeader(string $header): bool
    {
        return array_key_exists($header, $this->headers);
    }

    public function setAuthType(string $auth_type): self
    {
        $this->auth_type = $auth_type;
        return $this;
    }

    public function setBlocking(bool $blocking): self
    {
        $this->blocking = $blocking;
        return $this;
    }

    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setHeader(string $header, string $value): self
    {
        $value = trim($value);
        if (!empty($value)) {
            $this->headers[$header] = $value;
        }

        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setProtocolVersion($protocol_version): self
    {
        $this->protocol_version = $protocol_version;
        return $this;
    }

    public function setRedirectsCount(int $redirects_count): self
    {
        $this->redirects_count = $redirects_count;
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setUsername(string $user): self
    {
        $this->username = $user;
        return $this;
    }

    public function delete($url, $headers = null): Response
    {
        return $this->request($url, 'DELETE', $headers);
    }

    public function get($url, $headers = null): Response
    {
        return $this->request($url, 'GET', $headers);
    }

    public function head($url, $headers = null): Response
    {
        return $this->request($url, 'HEAD', $headers);
    }

    public function options($url, $headers = null, $content = null): Response
    {
        return $this->request($url, 'OPTIONS', $headers, $content);
    }

    public function patch($url, $headers = null, $content = null): Response
    {
        return $this->request($url, 'PATCH', $headers, $content);
    }

    public function post($url, $headers = null, $content = null): Response
    {
        return $this->request($url, 'POST', $headers, $content);
    }

    public function put($url, $headers = null, $content = null): Response
    {
        return $this->request($url, 'PUT', $headers, $content);
    }

    public function trace($url, $headers = null): Response
    {
        return $this->request($url, 'TRACE', $headers);
    }
}
/** End of RequestClientAbstract **/
