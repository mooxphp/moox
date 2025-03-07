<?php

declare(strict_types=1);

namespace Moox\Connect\Contracts;

interface ApiRequestInterface
{
    public function withHeader(string $name, string $value): self;

    public function withHeaders(array $headers): self;

    public function withQueryParam(string $name, string $value): self;

    public function withBody(mixed $body): self;

    public function getMethod(): string;

    public function getEndpoint(): string;

    public function getHeaders(): array;

    public function getQueryParams(): array;

    public function getBody(): mixed;

    public function getBaseUrl(): ?string;

    public function getFullUrl(): string;

    public function toArray(): array;
}
