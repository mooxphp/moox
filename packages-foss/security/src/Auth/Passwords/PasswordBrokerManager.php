<?php

namespace Moox\Security\Auth\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager as PasswordBrokerManagerBase;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Support\Str;
use Override;

class PasswordBrokerManager extends PasswordBrokerManagerBase
{
    /**
     * Create a token repository instance based on the given configuration.
     *
     * @return TokenRepositoryInterface
     */
    #[Override]
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr((string) $key, 7));
        }

        $connection = $config['connection'] ?? null;

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire']
        );
    }
}
