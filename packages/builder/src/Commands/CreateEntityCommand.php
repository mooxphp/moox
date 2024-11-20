<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Str;
use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Build\BuildStateManager;
use Moox\Builder\Services\Entity\EntityCreator;
use Moox\Builder\Services\Preview\PreviewTableManager;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity
        {name : The name of the entity}
        {--package= : Package namespace}
        {--preview : Generate in preview mode}
        {--preset= : Preset to use}
        {--plural= : Plural form of the entity name}';

    protected $description = 'Create a new entity';

    public function __construct(
        private readonly EntityCreator $entityCreator,
        private readonly BuildStateManager $buildStateManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $plural = $this->option('plural') ?? Str::plural($name);

        if (! $presetName = $this->option('preset')) {
            $presetName = $this->choice('Choose a preset', PresetRegistry::getPresetNames());
        }

        if (! $presetName) {
            $this->error('A preset is required for entity creation');

            return self::FAILURE;
        }

        try {
            $context = $this->createContext($name, $package, $preview);
            $blocks = PresetRegistry::getPresetBlocks($presetName);

            $this->buildStateManager->setContext($context);
            $this->entityCreator->setContext($context);
            $this->entityCreator->setBlocks($blocks);
            $this->entityCreator->setEntityData([
                'singular' => $name,
                'plural' => $plural,
                'description' => "A {$name} entity generated with Moox Builder",
            ]);

            if ($preview) {
                // Let EntityCreator handle preview table creation
                // It already has PreviewTableManager injected
            }

            $this->entityCreator->execute();

            $this->info("Entity {$name} created successfully in {$context->getContextType()} context");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create entity: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
