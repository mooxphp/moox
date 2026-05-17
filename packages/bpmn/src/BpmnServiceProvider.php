<?php

declare(strict_types=1);

namespace Moox\Bpmn;

use Illuminate\Support\Facades\Blade;
use Moox\Bpmn\View\Components\BpmnViewer;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BpmnServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('bpmn')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands()
            ->hasAssets();

        $mooxConfig = $this->getMooxConfig();
        $mooxPackage = $this->getMooxPackage();

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

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bpmn');

        Blade::component('bpmn-viewer', BpmnViewer::class);

        $this->app->singleton('bpmn-viewer', fn () => new BpmnViewer);
    }

    private function getMooxConfig(): array
    {
        $composer = json_decode(
            file_get_contents(__DIR__.'/../composer.json'),
            true
        );

        return $composer['extra']['moox'] ?? [];
    }
}
