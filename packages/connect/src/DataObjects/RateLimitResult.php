<?php

declare(strict_types=1);

namespace Moox\Connect\DataObjects;

use Carbon\Carbon;

final class RateLimitResult
{
    public function __construct(
        private bool $allowed,
        private int $limit,
        private int $remaining,
        private Carbon $reset
    ) {}

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getReset(): Carbon
    {
        return $this->reset;
    }
}
