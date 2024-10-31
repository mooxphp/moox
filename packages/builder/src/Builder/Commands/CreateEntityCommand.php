<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Moox\Builder\Builder\Services\EntityGenerator;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity {name} {--package=} {--preview} {--app}';

    protected $description = 'Create a new entity with model, resource and plugin';

    public function handle(): void
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $app = $this->option('app');

        if ($app && $package) {
            $this->error('Cannot specify both --app and --package options');

            return;
        }

        $context = $this->createContext($name, $package, $preview);

        (new EntityGenerator($context, [], []))->execute();

        $this->info("Entity {$name} created successfully!");
    }
}
