<?php

declare(strict_types=1);

namespace Usetall\TalluiWebComponents;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiWebComponents\Commands\TalluiWebComponentsCommand;
use Usetall\TalluiWebComponents\Components\BladeComponent;
use Usetall\TalluiWebComponents\Components\LivewireComponent;

class TalluiWebComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-web-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-web-components_table')
            ->hasCommand(TalluiWebComponentsCommand::class);
    }

    public function boot()
    {
        $this->bootResources();
        $this->bootBladeComponents();
        $this->bootLivewireComponents();
        $this->bootDirectives();
    }

    private function bootResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-web-components');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-web-components.prefix', '');
            $assets = config('tallui-web-components.assets', []);

            /** @var BladeComponent $component */
            foreach (config('tallui-web-components.components', []) as $alias => $component) {
                $blade->component($component, $alias, $prefix);

                $this->registerAssets($component, $assets);
            }
        });
    }

    private function bootLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        $prefix = config('tallui-web-components.prefix', '');
        $assets = config('tallui-web-components.assets', []);

        /** @var LivewireComponent $component */
        foreach (config('tallui-web-components.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    private function registerAssets($component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                TalluiWebComponents::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiWebComponents::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiWebComponentsStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiWebComponents\\TalluiWebComponents::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiWebComponentsScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiWebComponents\\TalluiWebComponents::outputScripts($expression); ?>";
        });
    }
}
