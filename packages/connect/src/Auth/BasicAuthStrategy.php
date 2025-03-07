<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Exceptions\ApiException;

final class BasicAuthStrategy extends BaseAuthStrategy
{
    private string $username;

    private string $password;

    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function authenticate(): void
    {
        if (empty($this->username) || empty($this->password)) {
            throw new ApiException(
                'Username and password are required',
                400,
                null,
                null,
                ['auth_type' => 'basic']
            );
        }

        $this->credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];
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
                ['auth_type' => 'basic']
            );
        }

        $credentials = base64_encode("{$this->username}:{$this->password}");

        return $request->withHeader('Authorization', "Basic {$credentials}");
    }

    public function refreshCredentials(): void
    {
        throw new ApiException(
            'Credential refresh not supported in basic authentication',
            400,
            null,
            null,
            ['auth_type' => 'basic']
        );
    }
}
