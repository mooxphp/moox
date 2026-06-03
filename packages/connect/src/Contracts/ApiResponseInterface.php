<?php

declare(strict_types=1);

namespace Moox\Connect\Contracts;

use Illuminate\Support\Carbon;

interface ApiResponseInterface
{
    public function getHeaders(): array;

    public function getStatusCode(): int;

    public function json(): array;

    public function isSuccessful(): bool;

    public function getRateLimit(): ?int;

    public function getRateRemaining(): ?int;

    public function getRateReset(): ?Carbon;
}
