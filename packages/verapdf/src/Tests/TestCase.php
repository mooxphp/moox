<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Tests;

use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Core\CoreServiceProvider;
use Moox\VeraPdf\VeraPdfServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\TestCase as ApplicationTestCase;

(static function (): void {
    foreach (spl_autoload_functions() ?: [] as $autoloader) {
        if (! is_array($autoloader)) {
            continue;
        }

        $loader = $autoloader[0];

        if ($loader instanceof ClassLoader) {
            $loader->addPsr4('Moox\\VeraPdf\\Tests\\', dirname(__DIR__, 2).'/tests');

            return;
        }
    }
})();

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
            $validations = include dirname(__DIR__, 2).'/database/migrations/create_verapdf_validations_table.php.stub';
            $validations->up();

            $validatables = include dirname(__DIR__, 2).'/database/migrations/create_verapdf_validatables_table.php.stub';
            $validatables->up();
        }
    }
} else {
    class TestCase extends ApplicationTestCase
    {
        use RefreshDatabase;
    }
}
