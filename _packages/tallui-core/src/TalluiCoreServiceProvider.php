<?php

declare(strict_types=1);

namespace Usetall\TalluiCore;

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiCore\Commands\TalluiCoreCommand;

class TalluiCoreServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-core')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-core_table')
            ->hasCommand(TalluiCoreCommand::class);
    }

    public function boot()
    {
        //$this->bootResources();
        $this->bootBladeComponents();
        $this->bootLivewireComponents();
        //$this->bootDirectives();
        //$this->bootPublishing();
    }

    /*
    private function bootResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-core');
    }
    */

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-core.prefix', '');
            $assets = config('tallui-core.assets', []);

            /** @var BladeComponent $component */
            foreach (config('tallui-core.components', []) as $alias => $component) {
                $blade->component($component, $alias, $prefix);

                //$this->registerAssets($component, $assets);
            }
        });
    }

    private function bootLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        $prefix = config('tallui-core.prefix', '');
        $assets = config('tallui-core.assets', []);

        /** @var LivewireComponent $component */
        foreach (config('tallui-core.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            //$this->registerAssets($component, $assets);
        }
    }

    /*
    private function registerAssets($component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                Usetall\TalluiCore::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                Usetall\TalluiCore::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('talluiCoreStyles', function (string $expression) {
            return "<?php echo Usetall\TalluiCore\\Usetall\TalluiCore::outputStyles($expression); ?>";
        });

        Blade::directive('talluiCoreScripts', function (string $expression) {
            return "<?php echo Usetall\TalluiCore\\Usetall\TalluiCore::outputScripts($expression); ?>";
        });
    }

    private function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tallui-core.php' => $this->app->configPath('tallui-core.php'),
            ], 'tallui-core-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/tallui-core'),
            ], 'tallui-core-views');
        }
    }
    */

}
