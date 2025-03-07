<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Exceptions\ApiException;

final class JwtAuthStrategy extends BaseAuthStrategy
{
    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    private string $secretKey;

    private string $algorithm;

    private ?Carbon $expiresAt = null;

    public function __construct(
        string $secretKey,
        string $algorithm = 'HS256',
        ?string $accessToken = null,
        ?string $refreshToken = null
    ) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function authenticate(): void
    {
        if (empty($this->accessToken)) {
            throw new ApiException(
                'JWT access token is required',
                400,
                null,
                null,
                ['auth_type' => 'jwt']
            );
        }

        try {
            $decoded = JWT::decode(
                $this->accessToken,
                new Key($this->secretKey, $this->algorithm)
            );

            $this->expiresAt = Carbon::createFromTimestamp($decoded->exp);

            if ($this->isTokenExpired()) {
                throw new ApiException(
                    'JWT token has expired',
                    401,
                    null,
                    null,
                    ['auth_type' => 'jwt']
                );
            }

            $this->credentials = [
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken,
                'expires_at' => $this->expiresAt->toIso8601String(),
            ];

            $this->authenticated = true;
        } catch (\Exception $e) {
            throw new ApiException(
                'JWT token validation failed: '.$e->getMessage(),
                401,
                null,
                null,
                ['auth_type' => 'jwt', 'original_exception' => get_class($e)]
            );
        }
    }

    public function applyToRequest(ApiRequestInterface $request): ApiRequestInterface
    {
        if (! $this->isAuthenticated()) {
            throw new ApiException(
                'Authentication required before making requests',
                401,
                null,
                null,
                ['auth_type' => 'jwt']
            );
        }

        if ($this->isTokenExpired()) {
            throw new ApiException(
                'JWT token has expired',
                401,
                null,
                null,
                ['auth_type' => 'jwt']
            );
        }

        return $request->withHeader('Authorization', "Bearer {$this->accessToken}");
    }

    public function refreshCredentials(): void
    {
        if (empty($this->refreshToken)) {
            throw new ApiException(
                'Refresh token is required',
                400,
                null,
                null,
                ['auth_type' => 'jwt']
            );
        }

        try {
            // Verify refresh token
            $decoded = JWT::decode(
                $this->refreshToken,
                new Key($this->secretKey, $this->algorithm)
            );

            // Generate new access token
            $payload = [
                'sub' => $decoded->sub,
                'iat' => time(),
                'exp' => time() + 3600, // 1 hour
            ];

            $this->accessToken = JWT::encode(
                $payload,
                $this->secretKey,
                $this->algorithm
            );

            $this->expiresAt = Carbon::createFromTimestamp($payload['exp']);
            $this->authenticated = false;

            // Re-authenticate with new token
            $this->authenticate();
        } catch (\Exception $e) {
            throw new ApiException(
                'Failed to refresh JWT token: '.$e->getMessage(),
                401,
                null,
                null,
                ['auth_type' => 'jwt', 'original_exception' => get_class($e)]
            );
        }
    }

    public function isTokenExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    public function setTokens(string $accessToken, ?string $refreshToken = null): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->authenticated = false;
    }

    public function hasRefreshCapability(): bool
    {
        return $this->refreshToken !== null;
    }
}
