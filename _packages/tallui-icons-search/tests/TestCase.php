<?php

namespace Usetall\TalluiIconsSearch\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Usetall\TalluiIconsSearch\TalluiIconsSearchServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');

        View::addNamespace('test', __DIR__.'/resources/views');

        $this
            ->registerLivewireComponents();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Adrolli\\TestRepoZwo\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            TalluiIconsSearchServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }

    private function registerLivewireComponents(): self
    {
        $prefix = config('tallui-icons-search.prefix', '');

        foreach (config('tallui-icons-search.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);
        }

        return $this;
    }
}
