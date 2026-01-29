<?php

declare(strict_types=1);

namespace Moox\Bpmn;

use Illuminate\Support\Facades\Blade;
use Moox\Bpmn\View\Components\BpmnViewer;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BpmnServiceProvider extends MooxServiceProvider
{
    /**
     * Configure the Moox package.
     */
    public function configureMoox(Package $package): void
    {
        $package
            ->name('bpmn')
            ->hasConfigFile()
            ->hasViews()              // loads ./resources/views
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands()
            ->hasAssets();            // exposes public assets

        // Load config from composer.json
        $mooxConfig = $this->getMooxConfig();
        $mooxPackage = $this->getMooxPackage();

        // Map dynamic config → Moox package metadata
        $configurable = [
            'title',
            'stability',
            'type',
            'category',
            'template',
        ];

        foreach ($configurable as $method) {
            if (isset($mooxConfig[$method]) && method_exists($mooxPackage, $method)) {
                $mooxPackage->{$method}($mooxConfig[$method]);
            }
        }
    }

    /**
     * Boot Moox + BPMN components.
     */
    public function boot(): void
    {
        parent::boot();

        // Ensure Blade views load
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bpmn');

        // Register <x-bpmn-viewer />
        Blade::component('bpmn-viewer', BpmnViewer::class);

        // Optional: service container binding
        $this->app->singleton('bpmn-viewer', fn () => new BpmnViewer);
    }

    /**
     * Read extra.moox config from composer.json.
     */
    private function getMooxConfig(): array
    {
        $composer = json_decode(
            file_get_contents(__DIR__.'/../composer.json'),
            true
        );

        return $composer['extra']['moox'] ?? [];
    }
}
