<?php

declare(strict_types=1);

namespace Moox\Connect\Http;

use InvalidArgumentException;
use Moox\Connect\Contracts\ApiRequestInterface;

final class ApiRequest implements ApiRequestInterface
{
    public function __construct(
        private readonly string $method,
        private readonly string $endpoint,
        private readonly array $headers = [],
        private readonly array $queryParams = [],
        private readonly mixed $body = null,
        private readonly ?string $baseUrl = null
    ) {
        $this->validateMethod($method);
        $this->method = strtoupper($method);
        $this->endpoint = $endpoint;
        $this->headers = $headers;
        $this->queryParams = $queryParams;
        $this->body = $body;
        $this->baseUrl = $baseUrl;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($clone->headers, $headers);

        return $clone;
    }

    public function withQueryParam(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->queryParams[$name] = $value;

        return $clone;
    }

    public function withBody(mixed $body): self
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function getFullUrl(): string
    {
        $url = $this->baseUrl ? rtrim($this->baseUrl, '/').'/' : '';
        $url .= ltrim($this->endpoint, '/');

        if (! empty($this->queryParams)) {
            $url .= '?'.http_build_query($this->queryParams);
        }

        return $url;
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'endpoint' => $this->endpoint,
            'headers' => $this->headers,
            'query_params' => $this->queryParams,
            'body' => $this->body,
            'base_url' => $this->baseUrl,
        ];
    }

    private function validateMethod(string $method): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        if (! in_array(strtoupper($method), $validMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }
    }
}
