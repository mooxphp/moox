<?php

declare(strict_types=1);

namespace Moox\Connect\Http;

use Illuminate\Support\Carbon;
use Moox\Connect\Contracts\ApiResponseInterface;

final class ApiResponse implements ApiResponseInterface
{
    public function __construct(
        private array $data,
        private int $statusCode,
        private array $headers,
        private ?int $rateLimit = null,
        private ?int $rateRemaining = null,
        private ?Carbon $rateReset = null
    ) {}

    public function withRateLimitHeaders(
        int $limit,
        int $remaining,
        Carbon $reset
    ): self {
        return new self(
            $this->data,
            $this->statusCode,
            [
                ...$this->headers,
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => $remaining,
                'X-RateLimit-Reset' => $reset->getTimestamp(),
            ],
            $limit,
            $remaining,
            $reset
        );
    }

    public function getRateLimit(): ?int
    {
        return $this->rateLimit;
    }

    public function getRateRemaining(): ?int
    {
        return $this->rateRemaining;
    }

    public function getRateReset(): ?Carbon
    {
        return $this->rateReset;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function json(): array
    {
        return $this->data;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
