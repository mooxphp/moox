<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Exceptions\ApiException;

final class BearerTokenStrategy extends BaseAuthStrategy
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function authenticate(): void
    {
        if (empty($this->token)) {
            throw new ApiException(
                'Bearer token is required',
                400,
                null,
                null,
                ['auth_type' => 'bearer']
            );
        }

        $this->credentials = ['token' => $this->token];
        $this->authenticated = true;
    }

    public function applyToRequest(ApiRequestInterface $request): ApiRequestInterface
    {
        if (! $this->isAuthenticated()) {
            throw new ApiException(
                'Authentication required before making requests',
                401,
                null,
                null,
                ['auth_type' => 'bearer']
            );
        }

        return $request->withHeader('Authorization', "Bearer {$this->token}");
    }

    public function refreshCredentials(): void
    {
        throw new ApiException(
            'Token refresh not supported in basic bearer authentication',
            400,
            null,
            null,
            ['auth_type' => 'bearer']
        );
    }
}
