<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Moox\Builder\Builder\PresetRegistry;
use Moox\Builder\Builder\Services\EntityGenerator;
use Moox\Builder\Builder\Services\PreviewMigrator;

class CreatePreviewCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-preview {name} {--preset=}';

    protected $description = 'Create a preview entity with migrations';

    public function handle(): void
    {
        $name = $this->argument('name');
        $presetName = $this->option('preset');

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

        $context = $this->createContext($name, preview: true);
        (new EntityGenerator($context, $preset->getBlocks(), $preset->getFeatures()))->execute();
        (new PreviewMigrator($context))->execute();

        $this->info("Preview entity {$name} created successfully using preset '{$presetName}'!");
    }
}
