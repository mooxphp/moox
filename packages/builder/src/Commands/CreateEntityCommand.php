<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\EntityGenerator;
use Moox\Builder\Services\PreviewMigrator;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create {name} {--package=} {--preview} {--app} {--preset=}';

    protected $description = 'Create a new entity with model, resource and plugin';

    public function handle(): void
    {
        $this->error('COMMAND START - If you see this, output works');

        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $app = $this->option('app');
        $presetName = $this->option('preset');

        $this->info('Starting entity creation...');
        $this->info('Context type: '.($preview ? 'preview' : ($package ? 'package' : 'app')));
        $this->info("Entity name: $name");

        if ($app && $package) {
            $this->error('Cannot specify both --app and --package options');

            return;
        }

        if (! $presetName) {
            $presetName = $this->choice(
                'Which preset would you like to use?',
                PresetRegistry::getAvailablePresets(),
                'simple-item'
            );
        }

        $preset = PresetRegistry::getPreset($presetName);
        if (! $preset) {
            $this->error("Preset '{$presetName}' not found. Available presets: ".implode(', ', PresetRegistry::getAvailablePresets()));

            return;
        }

        $context = $this->createContext($name, $package, $preview);
        $context->setPresetName($presetName);

        $this->error('Before generator execution');
        $generator = new EntityGenerator($context, $preset->getBlocks());
        $this->error('Generator instantiated');
        $generator->execute();
        $this->error('After generator execution');

        if ($preview) {
            $this->info('Running preview migration...');
            (new PreviewMigrator($context))->execute();
        }

        $this->info("Entity {$name} created successfully using preset '{$presetName}'!");
    }
}
