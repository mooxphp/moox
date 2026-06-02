<?php

declare(strict_types=1);

namespace Moox\Connect\Contracts;

interface AuthenticationStrategyInterface
{
    public function authenticate(): void;

    public function isAuthenticated(): bool;

    public function applyToRequest(ApiRequestInterface $request): ApiRequestInterface;

    public function refreshCredentials(): void;
}
