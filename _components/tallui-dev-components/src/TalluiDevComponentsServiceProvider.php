<?php

declare(strict_types=1);

namespace Usetall\TalluiDevComponents;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiDevComponents\Commands\TalluiDevComponentsCommand;

class TalluiDevComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-dev-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-dev-components_table')
            ->hasCommand(TalluiDevComponentsCommand::class);
    }

    public function boot(): void
    {
        PackageServiceProvider::boot();

        $this->bootResources();
        $this->bootBladeComponents();
        $this->bootLivewireComponents();
        $this->bootDirectives();
    }

    private function bootResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-dev-components');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-dev-components.prefix', '');
            $assets = config('tallui-dev-components.assets', []);

            foreach (config('tallui-dev-components.components', []) as $alias => $component) {
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

        $prefix = config('tallui-dev-components.prefix', '');
        $assets = config('tallui-dev-components.assets', []);

        foreach (config('tallui-dev-components.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    /** @param  array<mixed>  $assets */
    private function registerAssets(string $component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                TalluiDevComponents::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiDevComponents::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiDevComponentsStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiDevComponents\\TalluiDevComponents::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiDevComponentsScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiDevComponents\\TalluiDevComponents::outputScripts($expression); ?>";
        });
    }
}
