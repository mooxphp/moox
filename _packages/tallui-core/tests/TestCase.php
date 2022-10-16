<?php

namespace Usetall\TalluiCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Livewire\Livewire;
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
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/create_tallui_core_table.php.stub';
        $migration->up();
    }
}
