<?php

declare(strict_types=1);

namespace Moox\KositValidator\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Core\CoreServiceProvider;
use Moox\DevTools\Models\TestUser;
use Moox\KositValidator\KositValidatorServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\TestCase as ApplicationTestCase;

if (class_exists(Orchestra::class)) {
    #[WithMigration('laravel')]
    #[WithMigration('session')]
    class TestCase extends Orchestra
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            Factory::guessFactoryNamesUsing(
                fn (string $modelName): string => 'Moox\\KositValidator\\Database\\Factories\\'.class_basename($modelName).'Factory'
            );
        }

        protected function getPackageProviders($app): array
        {
            return [
                CoreServiceProvider::class,
                KositValidatorServiceProvider::class,
            ];
        }

        protected function getEnvironmentSetUp($app): void
        {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
            $app['config']->set('database.default', 'testing');
            $app['config']->set('session.driver', 'array');
            $app['config']->set('auth.providers.users.model', TestUser::class);
            $app['config']->set('auth.guards.web.provider', 'users');
        }

        protected function defineDatabaseMigrations(): void
        {
            $migration = include dirname(__DIR__).'/database/migrations/create_kosit_validations_table.php.stub';

            $migration->up();
        }
    }
} else {
    class TestCase extends ApplicationTestCase
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            config()->set('auth.providers.users.model', TestUser::class);
        }
    }
}
