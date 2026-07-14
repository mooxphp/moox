<?php

declare(strict_types=1);

namespace Moox\Page\Tests;

use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Moox\Page\Database\Factories\PageFactory;
use Moox\Page\PageServiceProvider;
use Moox\Page\Support\BlockContentRendererAdapter;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\TestCase as ApplicationTestCase;

(static function (): void {
    foreach (spl_autoload_functions() ?: [] as $autoloader) {
        if (! is_array($autoloader)) {
            continue;
        }

        $loader = $autoloader[0];

        if ($loader instanceof ClassLoader) {
            $loader->addPsr4('Moox\\Page\\Tests\\', __DIR__);

            return;
        }
    }
})();

if (class_exists(Orchestra::class)) {
    abstract class TestCase extends Orchestra
    {
        use Concerns\CreatesPageSchema;
        use RefreshDatabase;

        protected function setUp(): void
        {
            parent::setUp();

            $this->configurePagePackageTests();
        }

        protected function defineDatabaseMigrations(): void
        {
            $this->setUpPageSchema();
        }

        protected function getPackageProviders($app): array
        {
            return [
                \Moox\Core\CoreServiceProvider::class,
                \Moox\Localization\LocalizationServiceProvider::class,
                \Moox\BlockEditor\BlockEditorServiceProvider::class,
                PageServiceProvider::class,
            ];
        }

        protected function getEnvironmentSetUp($app): void
        {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
            $app['config']->set('database.default', 'testing');
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
            $app['config']->set('session.driver', 'array');
            $app['config']->set('localization.enable-panel', false);
            $this->applyPageTestConfig($app);
        }

        protected function configurePagePackageTests(): void
        {
            Factory::guessFactoryNamesUsing(
                fn (string $modelName): string => PageFactory::class
            );
        }

        protected function applyPageTestConfig(mixed $app): void
        {
            $config = is_object($app) && isset($app['config']) ? $app['config'] : config();

            $config->set('page.cache.enabled', false);
            $config->set('page.frontend.enabled', true);
            $config->set('page.taxonomies', []);
            $config->set('page.user_models', [
                \Illuminate\Foundation\Auth\User::class => [
                    'title_attribute' => 'name',
                    'label' => 'User',
                ],
            ]);
            $config->set('page.content_renderer', BlockContentRendererAdapter::class);

            View::addLocation(__DIR__.'/stubs/views');
        }
    }
} else {
    abstract class TestCase extends ApplicationTestCase
    {
        use Concerns\CreatesPageSchema;

        protected function setUp(): void
        {
            parent::setUp();

            $this->setUpPageSchema();
            $this->configurePagePackageTests();
            $this->applyPageTestConfig($this->app);
        }

        protected function configurePagePackageTests(): void
        {
            Factory::guessFactoryNamesUsing(
                fn (string $modelName): string => PageFactory::class
            );
        }

        protected function applyPageTestConfig(mixed $app): void
        {
            $config = is_object($app) && isset($app['config']) ? $app['config'] : config();

            $config->set('page.cache.enabled', false);
            $config->set('page.frontend.enabled', true);
            $config->set('page.taxonomies', []);
            $config->set('page.user_models', [
                \App\Models\User::class => [
                    'title_attribute' => 'name',
                    'label' => 'User',
                ],
            ]);

            View::addLocation(__DIR__.'/stubs/views');
        }
    }
}
