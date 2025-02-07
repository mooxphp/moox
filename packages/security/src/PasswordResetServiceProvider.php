<?php

namespace Moox\Security;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider as PasswordResetServiceProviderBase;
use Moox\Security\Auth\Passwords\PasswordBrokerManager;
use Override;

class PasswordResetServiceProvider extends PasswordResetServiceProviderBase
{
    /**
     * Register the password broker instance.
     *
     * @return void
     */
    #[Override]
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', fn ($app): PasswordBrokerManager => new PasswordBrokerManager($app));

        $this->app->bind('auth.password.broker', fn ($app) => $app->make('auth.password')->broker());
    }
}
