<?php

declare(strict_types=1);

namespace Moox\Connect\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Connect\Auth\BasicAuthStrategy;
use Moox\Connect\Auth\BearerTokenStrategy;
use Moox\Connect\Auth\GraphQLAuthStrategy;
use Moox\Connect\Auth\JwtAuthStrategy;
use Moox\Connect\Contracts\AuthenticationStrategyInterface;
use Moox\Connect\Exceptions\ApiException;

class OLD_ApiConnection extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'api_type',
        'auth_type',
        'auth_credentials',
        'headers',
        'rate_limit',
        'lang_param',
        'default_locale',
        'status',
        'notify_on_failure',
        'notify_email',
    ];

    protected $casts = [
        'auth_credentials' => 'array',
        'headers' => 'array',
        'notify_on_failure' => 'boolean',
        'rate_limit' => 'integer',
    ];

    /*
    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class);
    }
    */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function createAuthStrategy(): AuthenticationStrategyInterface
    {
        return match ($this->auth_type) {
            'basic' => new BasicAuthStrategy(
                username: $this->auth_credentials['username'] ?? throw new ApiException('Username is required', 400),
                password: $this->auth_credentials['password'] ?? throw new ApiException('Password is required', 400)
            ),
            'bearer' => new BearerTokenStrategy(
                token: $this->auth_credentials['token'] ?? throw new ApiException('Bearer token is required', 400)
            ),
            'jwt' => new JwtAuthStrategy(
                secretKey: $this->auth_credentials['secret_key'] ?? throw new ApiException('JWT secret key is required', 400),
                algorithm: $this->auth_credentials['algorithm'] ?? 'HS256',
                accessToken: $this->auth_credentials['access_token'] ?? null,
                refreshToken: $this->auth_credentials['refresh_token'] ?? null
            ),
            'graphql' => new GraphQLAuthStrategy(
                loginMutation: $this->auth_credentials['login_mutation'] ?? throw new ApiException('Login mutation is required', 400),
                variables: $this->auth_credentials['variables'] ?? [],
                refreshMutation: $this->auth_credentials['refresh_mutation'] ?? null
            ),
            default => throw new ApiException("Unsupported auth type: {$this->auth_type}", 400)
        };
    }

    public function activate(): void
    {
        if ($this->status === 'disabled') {
            throw new ApiException('Cannot activate disabled connection', 400);
        }
        $this->status = 'active';
        $this->save();
    }

    public function disable(): void
    {
        $this->status = 'disabled';
        $this->save();
    }

    public function markError(string $message): void
    {
        $this->status = 'error';
        $this->save();

        if ($this->notify_on_failure) {
            // Notification logic here
        }
    }

    public function getNotificationEmail(): ?string
    {
        return $this->notify_email
            ?? config('connect.notifications.email')
            ?? config('mail.to.address');
    }
}
