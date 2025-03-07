<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Exceptions\ApiException;

final class GraphQLAuthStrategy extends BaseAuthStrategy
{
    private string $loginMutation;

    private string $refreshMutation;

    private array $variables;

    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    public function __construct(
        string $loginMutation,
        array $variables,
        ?string $refreshMutation = null
    ) {
        $this->loginMutation = $loginMutation;
        $this->variables = $variables;
        $this->refreshMutation = $refreshMutation;
    }

    public function authenticate(): void
    {
        if (empty($this->loginMutation) || empty($this->variables)) {
            throw new ApiException(
                'Login mutation and variables are required',
                400,
                null,
                null,
                ['auth_type' => 'graphql']
            );
        }

        // Note: Actual authentication will be performed by the GraphQL client
        // This just prepares the credentials for use
        $this->credentials = [
            'mutation' => $this->loginMutation,
            'variables' => $this->variables,
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
                ['auth_type' => 'graphql']
            );
        }

        if ($this->accessToken === null) {
            throw new ApiException(
                'No access token available',
                401,
                null,
                null,
                ['auth_type' => 'graphql']
            );
        }

        return $request->withHeader('Authorization', "Bearer {$this->accessToken}");
    }

    public function refreshCredentials(): void
    {
        if ($this->refreshMutation === null) {
            throw new ApiException(
                'No refresh mutation configured',
                400,
                null,
                null,
                ['auth_type' => 'graphql']
            );
        }

        if ($this->refreshToken === null) {
            throw new ApiException(
                'No refresh token available',
                401,
                null,
                null,
                ['auth_type' => 'graphql']
            );
        }

        // Note: Actual refresh will be performed by the GraphQL client
        $this->credentials = [
            'mutation' => $this->refreshMutation,
            'variables' => ['refreshToken' => $this->refreshToken],
        ];
    }

    public function setTokens(string $accessToken, ?string $refreshToken = null): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function hasRefreshCapability(): bool
    {
        return $this->refreshMutation !== null && $this->refreshToken !== null;
    }
}
