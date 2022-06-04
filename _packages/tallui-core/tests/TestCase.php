<?php

namespace Usetall\TalluiCore\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Usetall\TalluiCore\TalluiCoreServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Usetall\\TalluiCore\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
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

        $app['config']->set('view.paths', [
            __DIR__.'/../views',
            resource_path('views'),
        ]);

        $migration = include __DIR__.'/../database/migrations/create_tallui-core_table.php.stub';
        $migration->up();
    }
}
