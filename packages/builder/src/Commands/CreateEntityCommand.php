<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Build\BuildManager;
use Moox\Builder\Services\Entity\EntityCreator;
use Moox\Builder\Services\Entity\EntityGenerator;
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
        private readonly BuildManager $buildManager,
        private readonly EntityGenerator $entityGenerator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $presetName = $this->option('preset') ?? $this->choice('Choose a preset', PresetRegistry::getPresetNames());
        $buildContext = $this->getBuildContext($preview, $package);

        $result = $this->entityCreator->create($name, $buildContext, $presetName);
        $entity = $result['entity'];

        if (! $entity) {
            $this->error("Failed to create entity {$name}");

            return self::FAILURE;
        }

        $blocks = $result['blocks'];
        $context = $this->createContext($name, $package, $preview);
        $context->setPresetName($presetName);

        $this->entityGenerator->setContext($context);
        $this->entityGenerator->setBlocks($blocks);
        $this->entityGenerator->execute();

        if ($preview) {
            $this->previewManager->createPreviewTable($name, $blocks);
        }

        $this->buildManager->recordBuild(
            $entity->id,
            $buildContext,
            $blocks,
            $preview ? [] : $this->entityGenerator->getGeneratedFiles()
        );

        $this->info('Entity '.$name.' '.($result['status'] === 'exists' ? 're' : '').'built successfully in '.$buildContext);

        return self::SUCCESS;
    }
}
