<?php

namespace Moox\Prompts;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

use Moox\Prompts\Support\PromptRuntime;
use Moox\Prompts\Support\CliPromptRuntime;

class PromptsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('moox-prompts')
            ->hasConfigFile('prompts');
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(PromptRuntime::class, function ($app) {
            return new CliPromptRuntime();
        });
    }

    public function boot(): void
    {
        parent::boot();

        require_once __DIR__.'/functions.php';
    }
}
