<?php

declare(strict_types=1);

namespace Usetall\TalluiFormComponents;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiFormComponents\Components\BladeComponent;
use Usetall\TalluiFormComponents\Components\LivewireComponent;

class TalluiFormComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-form-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-form-components_table')
            ->hasCommand(TalluiFormComponentsCommand::class);
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-form-components');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-form-components.prefix', '');
            $assets = config('tallui-form-components.assets', []);

            /** @var BladeComponent $component */
            foreach (config('tallui-form-components.components', []) as $alias => $component) {
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

        $prefix = config('tallui-form-components.prefix', '');
        $assets = config('tallui-form-components.assets', []);

        /** @var LivewireComponent $component */
        foreach (config('tallui-form-components.livewire', []) as $alias => $component) {
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
                TalluiFormComponents::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiFormComponents::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiFormComponentsStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiFormComponents\\TalluiFormComponents::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiFormComponentsScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiFormComponents\\TalluiFormComponents::outputScripts($expression); ?>";
        });
    }
}
