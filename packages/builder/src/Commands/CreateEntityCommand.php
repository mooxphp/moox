<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Entity\EntityCreator;
use Moox\Builder\Services\Preview\PreviewManager;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity
        {name : The name of the entity}
        {--package= : Package namespace}
        {--preview : Generate in preview mode}
        {--preset= : Preset to use}';

    protected $description = 'Create a new entity';

    public function __construct(
        private readonly EntityCreator $entityCreator,
        private readonly PreviewManager $previewManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $buildContext = $preview ? 'preview' : ($package ? 'package' : 'app');

        if ($presetName = $this->option('preset') ?? $this->choice('Choose a preset', PresetRegistry::getPresetNames())) {
            $context = $this->createContext($name, $package, $preview);
            $context->setPresetName($presetName);

            $this->entityCreator->setContext($context);
            $result = $this->entityCreator->createFromPreset($name, $buildContext, $presetName);
        } else {
            $this->error('A preset is required for entity creation');

            return self::FAILURE;
        }

        $entity = $result['entity'];
        if (! $entity) {
            $this->error("Failed to create entity {$name}");

            return self::FAILURE;
        }

        if ($preview) {
            $this->previewManager->createPreviewTable($name, $result['blocks']);
        }

        $this->info('Entity '.$name.' '.($result['status'] === 'exists' ? 're' : '').'built successfully in '.$buildContext);

        return self::SUCCESS;
    }
}
