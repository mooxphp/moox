<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Core\CoreServiceProvider;
use Moox\VeraPdf\VeraPdfServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\TestCase as ApplicationTestCase;

if (class_exists(Orchestra::class)) {
    #[WithMigration('laravel')]
    class TestCase extends Orchestra
    {
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();
        }

        /**
         * @return list<class-string>
         */
        protected function getPackageProviders($app): array
        {
            return [
                CoreServiceProvider::class,
                VeraPdfServiceProvider::class,
            ];
        }

        protected function getEnvironmentSetUp($app): void
        {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
            $app['config']->set('database.default', 'testing');
            $app['config']->set('session.driver', 'array');
        }

        protected function defineDatabaseMigrations(): void
        {
            $validations = include dirname(__DIR__).'/database/migrations/create_verapdf_validations_table.php.stub';
            $validations->up();

            $validatables = include dirname(__DIR__).'/database/migrations/create_verapdf_validatables_table.php.stub';
            $validatables->up();
        }
    }
} else {
    class TestCase extends ApplicationTestCase
    {
        use RefreshDatabase;
    }
}
