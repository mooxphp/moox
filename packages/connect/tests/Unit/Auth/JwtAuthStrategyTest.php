<?php

namespace Moox\Connect\Tests\Unit\Auth;

use Firebase\JWT\JWT;
use Moox\Connect\Auth\JwtAuthStrategy;
use Moox\Connect\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;

class JwtAuthStrategyTest extends TestCase
{
    private string $secretKey = 'test-secret-key';

    private string $algorithm = 'HS256';

    public function test_it_authenticates_with_valid_token(): void
    {
        $payload = [
            'sub' => '123',
            'exp' => time() + 3600,
        ];

        $token = JWT::encode($payload, $this->secretKey, $this->algorithm);

        $auth = new JwtAuthStrategy(
            $this->secretKey,
            $this->algorithm,
            $token
        );

        $auth->authenticate();

        $this->assertTrue($auth->isAuthenticated());
    }

    public function test_it_throws_exception_for_expired_token(): void
    {
        $payload = [
            'sub' => '123',
            'exp' => time() - 3600,
        ];

        $token = JWT::encode($payload, $this->secretKey, $this->algorithm);

        $auth = new JwtAuthStrategy(
            $this->secretKey,
            $this->algorithm,
            $token
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('JWT token has expired');

        $auth->authenticate();
    }

    public function test_it_refreshes_token(): void
    {
        $refreshPayload = [
            'sub' => '123',
            'exp' => time() + 7200,
        ];

        $refreshToken = JWT::encode($refreshPayload, $this->secretKey, $this->algorithm);

        $auth = new JwtAuthStrategy(
            $this->secretKey,
            $this->algorithm,
            null,
            $refreshToken
        );

        $auth->refreshCredentials();

        $this->assertTrue($auth->isAuthenticated());
        $this->assertNotNull($auth->getCredentials()['access_token']);
    }
}
