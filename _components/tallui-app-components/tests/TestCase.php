<?php

namespace Usetall\TalluiAppComponents\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Usetall\TalluiAppComponents\TalluiAppComponentsServiceProvider;

class TestCase extends Orchestra
{

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Usetall\\TalluiAppComponents\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            TalluiAppComponentsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_tallui-app-components_table.php.stub';
        $migration->up();
        */
    }
}
