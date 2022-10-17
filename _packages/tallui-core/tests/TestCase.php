<?php

namespace Usetall\TalluiCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
// Without Livwire the test runs but ...

use Livewire\Livewire;
use Orchestra\Testbench\TestCase as Orchestra;
use Usetall\TalluiCore\Components\Livewire\CoreLivewire;
// We also load Livewire here, and of course want to test it

use Usetall\TalluiCore\TalluiCoreServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Usetall\\TalluiCore\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->registerLivewireComponents();
    }

    // Taken from https://github.com/livewire/livewire/discussions/4705

    protected function registerLivewireComponents()
    {
        Livewire::component('core-livewire', CoreLivewire::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            TalluiCoreServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        /*

        $app['config']->set('view.paths', [
            __DIR__.'/../views',
            resource_path('views'),
        ]);

        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');
        */

        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/create_tallui_core_table.php.stub';
        $migration->up();
    }
}
