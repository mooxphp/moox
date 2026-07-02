<?php

declare(strict_types=1);

namespace Moox\BlockEditor;

use Illuminate\Support\Facades\Gate;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Policies\TemplatePolicy;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BlockEditorServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('block-editor')
            ->hasConfigFile('moox-editor')
            ->hasViews('moox-editor')
            ->hasMigrations()
            ->hasRoutes('api');
    }

    public function bootingPackage(): void
    {
        Gate::policy(Template::class, TemplatePolicy::class);
    }

    public function packageBooted(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../resources/editor' => public_path('vendor/moox/block-editor'),
            __DIR__.'/../resources/js/browser@4.js' => public_path('vendor/moox/block-editor/browser@4.js'),
        ], 'moox-editor-assets');
    }
}
