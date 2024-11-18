<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Entity\EntityCreator;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity
        {name : The name of the entity}
        {--package= : Package namespace}
        {--preview : Generate in preview mode}
        {--preset= : Preset to use}';

    protected $description = 'Create a new entity';

    public function __construct(
        private readonly EntityCreator $entityCreator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');

        if ($presetName = $this->option('preset') ?? $this->choice('Choose a preset', PresetRegistry::getPresetNames())) {
            $context = $this->createContext($name, $package, $preview);
            $this->entityCreator->setContext($context);
            $this->entityCreator->setBlocks(PresetRegistry::getPresetBlocks($presetName));
            $this->entityCreator->execute();

            $this->info("Entity {$name} created successfully in {$context->getContext()} context");

            return self::SUCCESS;
        }

        $this->error('A preset is required for entity creation');

        return self::FAILURE;
    }
}
