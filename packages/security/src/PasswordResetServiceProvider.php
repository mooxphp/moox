<?php

namespace Moox\Security;

use Illuminate\Auth\Passwords\PasswordResetServiceProvider as PasswordResetServiceProviderBase;
use Moox\Security\Auth\Passwords\PasswordBrokerManager;

class PasswordResetServiceProvider extends PasswordResetServiceProviderBase
{
    /**
     * Register the password broker instance.
     *
     * @return void
     */
    protected function registerPasswordBroker()
    {
        $this->app->singleton('auth.password', function ($app) {
            return new PasswordBrokerManager($app);
        });

        $this->app->bind('auth.password.broker', function ($app) {
            return $app->make('auth.password')->broker();
        });
    }
}
