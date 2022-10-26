<?php

namespace VendorName\Skeleton\Tests;

use DOMDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Livewire\Testing\TestableLivewire;
use Orchestra\Testbench\TestCase as Orchestra;
use VendorName\Skeleton\SkeletonServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');

        View::addNamespace('test', __DIR__.'/resources/views');

        $this
            ->registerLivewireComponents()
            ->registerLivewireTestMacros();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Adrolli\\TestRepoZwo\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            SkeletonServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_skeleton_table.php.stub';
        $migration->up();
        */
    }

    private function registerLivewireComponents(): self
    {
        $prefix = config(':builder.prefix', '');
        $assets = config(':builder.assets', []);

        foreach (config(':builder.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            //$this->registerAssets($component, $assets);
        }

        return $this;
    }

    public function registerLivewireTestMacros(): self
    {
        TestableLivewire::macro('jsonContent', function (string $elementId) {
            $document = new DOMDocument();

            //$document->loadHTML($this->lastRenderedDom);

            $content = $document->getElementById($elementId)->textContent;

            return json_decode($content, true);
        });

        TestableLivewire::macro('htmlContent', function (string $elementId) {
            $document = new DOMDocument();

            $document->preserveWhiteSpace = false;

            //$document->loadHTML($this->lastRenderedDom);

            $domNode = $document->getElementById($elementId);

            return Str::of($document->saveHTML($domNode))
                ->replace("\n", "\r\n")
                ->trim()
                ->toString();
        });

        return $this;
    }
}
