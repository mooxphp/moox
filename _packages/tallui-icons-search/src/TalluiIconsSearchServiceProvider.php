<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiIconsSearch\Commands\TalluiIconsSearchCommand;

class TalluiIconsSearchServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-icons-search')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('2023_02_27_112206_create_icons_table')
            ->runsMigrations(true)
            ->hasCommand(TalluiIconsSearchCommand::class);
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-icons-search');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-icons-search.prefix', '');
            $assets = config('tallui-icons-search.assets', []);

            foreach (config('tallui-icons-search.components', []) as $alias => $component) {
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

        $prefix = config('tallui-icons-search.prefix', '');
        $assets = config('tallui-icons-search.assets', []);

        foreach (config('tallui-icons-search.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    /** @param  array<mixed>  $assets*/
    private function registerAssets(mixed $component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array) ($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                TalluiIconsSearch::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiIconsSearch::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiIconsSearchStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiIconsSearch\\TalluiIconsSearch::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiIconsSearchScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiIconsSearch\\TalluiIconsSearch::outputScripts($expression); ?>";
        });
    }
}
