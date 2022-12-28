<?php

declare(strict_types=1);

namespace Usetall\TalluiAdminPanel;

use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Usetall\TalluiAdminPanel\Commands\TalluiAdminPanelCommand;
use Usetall\TalluiAdminPanel\Http\Controllers\AdminPanelController;

class TalluiAdminPanelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tallui-admin-panel')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_tallui-admin-panel_table')
            ->hasCommand(TalluiAdminPanelCommand::class);
    }

    public function packageRegistered() : void
    {
        Route::macro('tui', function (string $baseUrl = 'tui') {
            Route::prefix($baseUrl)->group(function () {
                Route::get('/', [AdminPanelController::class, 'index']);
            });
        });
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tallui-admin-panel');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('tallui-admin-panel.prefix', '');
            $assets = config('tallui-admin-panel.assets', []);

            foreach (config('tallui-admin-panel.components', []) as $alias => $component) {
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

        $prefix = config('tallui-admin-panel.prefix', '');
        $assets = config('tallui-admin-panel.assets', []);

        foreach (config('tallui-admin-panel.livewire', []) as $alias => $component) {
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
                TalluiAdminPanel::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                TalluiAdminPanel::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('TalluiAdminPanelStyles', function (string $expression) {
            return "<?php echo Usetall\\TalluiAdminPanel\\TalluiAdminPanel::outputStyles($expression); ?>";
        });

        Blade::directive('TalluiAdminPanelScripts', function (string $expression) {
            return "<?php echo Usetall\\TalluiAdminPanel\\TalluiAdminPanel::outputScripts($expression); ?>";
        });
    }
}
