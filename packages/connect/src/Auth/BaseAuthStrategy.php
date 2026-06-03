<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Contracts\AuthenticationStrategyInterface;
use Moox\Connect\Exceptions\ApiException;

abstract class BaseAuthStrategy implements AuthenticationStrategyInterface
{
    protected bool $authenticated = false;

    protected array $credentials = [];

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function clearCredentials(): void
    {
        $this->credentials = [];
        $this->authenticated = false;
    }

    public function authenticate(): void
    {
        if (! $this->isAuthenticated()) {
            throw new ApiException(
                'Authentication required',
                401,
                null,
                null,
                ['auth_strategy' => static::class]
            );
        }
    }

    abstract public function applyToRequest(ApiRequestInterface $request): ApiRequestInterface;

    abstract public function refreshCredentials(): void;
}
