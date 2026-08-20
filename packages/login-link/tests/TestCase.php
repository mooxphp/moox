<?php

declare(strict_types=1);

namespace Moox\LoginLink\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\LoginLink\LoginLinkServiceProvider;
use Moox\LoginLink\Tests\Support\TestUser;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LoginLinkServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('login-link.passwordless.enabled', true);
        config()->set('login-link.rate_limit.send', [
            'max_attempts' => 3,
            'decay_seconds' => 60,
            'ip_max_attempts' => 5,
            'ip_decay_seconds' => 60,
        ]);
        config()->set('login-link.expiration_minutes', 60);
        config()->set('login-link.user_models', [
            'Test User' => TestUser::class,
        ]);
        config()->set('login-link.templates', [
            'login' => 'login-link::mail.login-link',
            'verify-email' => 'login-link::mail.verify-email',
            'mass-mail' => 'login-link::mail.mass-mail',
            'ack' => 'login-link::mail.process-link',
        ]);
        config()->set('login-link.public_consume_path', 'signed-link/{loginLink}');
        config()->set('login-link.ack.redirect_url', '/ack-ok');
        config()->set('auth.providers.users.model', TestUser::class);
        config()->set('auth.guards.web.provider', 'users');
    }

    protected function defineDatabaseMigrations(): void
    {
        $loginLinks = include __DIR__.'/../database/migrations/create_login_links_table.php.stub';
        $loginLinks->up();

        $processes = include __DIR__.'/../database/migrations/create_login_link_processes_table.php.stub';
        $processes->up();
    }
}
