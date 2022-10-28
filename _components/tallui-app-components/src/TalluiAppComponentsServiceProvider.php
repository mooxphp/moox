<?php

declare(strict_types=1);

namespace Usetall\TalluiAppComponents;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiAppComponents\Commands\TalluiAppComponentsCommand;

class TalluiAppComponentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-app-components')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-app-components_table')
            ->hasCommand(TalluiAppComponentsCommand::class);
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-app-components');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-app-components.prefix', '');
            $assets = config('tallui-app-components.assets', []);

            foreach (config('tallui-app-components.components', []) as $alias => $component) {
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

        $prefix = config('tallui-app-components.prefix', '');
        $assets = config('tallui-app-components.assets', []);

        foreach (config('tallui-app-components.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    private function registerAssets(string $component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                TalluiAppComponents::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiAppComponents::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiAppComponentsStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiAppComponents\\TalluiAppComponents::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiAppComponentsScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiAppComponents\\TalluiAppComponents::outputScripts($expression); ?>";
        });
    }
}
