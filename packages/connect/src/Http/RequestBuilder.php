<?php

declare(strict_types=1);

namespace Moox\Connect\Http;

final class RequestBuilder
{
    private string $method = 'GET';

    private string $endpoint = '';

    private array $headers = [];

    private array $queryParams = [];

    private mixed $body = null;

    private ?string $baseUrl = null;

    public static function create(): self
    {
        return new self;
    }

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function get(): self
    {
        return $this->method('GET');
    }

    public function post(): self
    {
        return $this->method('POST');
    }

    public function put(): self
    {
        return $this->method('PUT');
    }

    public function patch(): self
    {
        return $this->method('PATCH');
    }

    public function delete(): self
    {
        return $this->method('DELETE');
    }

    public function endpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function query(string $name, string $value): self
    {
        $this->queryParams[$name] = $value;

        return $this;
    }

    public function queryParams(array $params): self
    {
        $this->queryParams = array_merge($this->queryParams, $params);

        return $this;
    }

    public function body(mixed $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function json(array $data): self
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->body = json_encode($data, JSON_THROW_ON_ERROR);

        return $this;
    }

    public function build(): ApiRequest
    {
        return new ApiRequest(
            $this->method,
            $this->endpoint,
            $this->headers,
            $this->queryParams,
            $this->body,
            $this->baseUrl
        );
    }
}
