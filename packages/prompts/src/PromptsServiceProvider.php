<?php

namespace Moox\Prompts;

use Moox\Core\MooxServiceProvider;
use Moox\Prompts\Support\CliPromptRuntime;
use Moox\Prompts\Support\PromptFlowRunner;
use Moox\Prompts\Support\PromptFlowStateStore;
use Moox\Prompts\Support\PromptResponseStore;
use Moox\Prompts\Support\PromptRuntime;
use Moox\Prompts\Support\WebPromptRuntime;
use Spatie\LaravelPackageTools\Package;

class PromptsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('moox-prompts')
            ->hasConfigFile('prompts')
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_command_executions_table']);
    }

    public function register()
    {
        parent::register();

        $this->app->bind('moox.prompts.response_store', function ($app) {
            return new PromptResponseStore;
        });

        $this->app->singleton(PromptFlowStateStore::class, function ($app) {
            $store = cache()->store();

            // Avoid volatile in-memory array cache which loses flow state between requests.
            if ($store->getStore() instanceof \Illuminate\Cache\ArrayStore) {
                $store = cache()->store('file');
            }

            return new PromptFlowStateStore(
                $store,
                'moox_prompts_flow:',
                3600
            );
        });

        $this->app->singleton(PromptFlowRunner::class, function ($app) {
            return new PromptFlowRunner(
                $app->make(\Illuminate\Contracts\Console\Kernel::class),
                $app->make(PromptFlowStateStore::class)
            );
        });

        $this->app->singleton(PromptRuntime::class, function ($app) {
            if (php_sapi_name() === 'cli') {
                return new CliPromptRuntime;
            }

            return new WebPromptRuntime;
        });
    }

    public function bootingPackage(): void
    {
        require_once __DIR__.'/functions.php';

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'moox-prompts');

        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component(
                'moox-prompts.filament.components.run-command-component',
                \Moox\Prompts\Filament\Components\RunCommandComponent::class
            );
        }
    }
}
