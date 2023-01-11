<?php

declare(strict_types=1);

namespace Usetall\TalluiPackageBuilder;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiPackageBuilder\Commands\TalluiPackageBuilderCommand;

class TalluiPackageBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('skeleton')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_skeleton_table')
            ->hasCommand(TalluiPackageBuilderCommand::class);
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
        $this->loadViewsFrom(__DIR__ . '/../resources/views', ':builder');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config(':builder.prefix', '');
            $assets = config(':builder.assets', []);

            foreach (config(':builder.components', []) as $alias => $component) {
                $blade->component($component, $alias, $prefix);

                $this->registerAssets($component, $assets);
            }
        });
    }

    private function bootLivewireComponents(): void
    {
        if (!class_exists(Livewire::class)) {
            return;
        }

        $prefix = config(':builder.prefix', '');
        $assets = config(':builder.assets', []);

        foreach (config(':builder.livewire', []) as $alias => $component) {
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
                TalluiPackageBuilder::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiPackageBuilder::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiPackageBuilderStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiPackageBuilder\\TalluiPackageBuilder::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiPackageBuilderScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiPackageBuilder\\TalluiPackageBuilder::outputScripts($expression); ?>";
        });
    }
}
