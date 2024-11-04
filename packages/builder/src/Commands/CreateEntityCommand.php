<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\EntityGenerator;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity {name} {--package=} {--preview} {--app} {--preset=}';

    protected $description = 'Create a new entity with model, resource and plugin';

    public function handle(): void
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $app = $this->option('app');
        $presetName = $this->option('preset');

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
        (new EntityGenerator($context, $preset->getBlocks(), $preset->getFeatures()))->execute();

        $this->info("Entity {$name} created successfully using preset '{$presetName}'!");
    }
}
