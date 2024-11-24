<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Contexts\ContextFactory;
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
        private readonly PreviewTableManager $previewTableManager,
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
            Log::info('Starting entity creation', [
                'name' => $name,
                'package' => $package,
                'preview' => $preview,
                'preset' => $presetName,
            ]);

            $context = $this->createContext($name, $package, $preview);
            $blocks = PresetRegistry::getPresetBlocks($presetName);

            if (empty($blocks)) {
                $this->error("Preset '{$presetName}' returned no blocks");

                return self::FAILURE;
            }

            Log::info('Context created', [
                'contextType' => $context->getContextType(),
            ]);

            $this->buildStateManager->setContext($context);
            $this->entityCreator->setContext($context);
            $this->entityCreator->setBlocks($blocks);
            $this->entityCreator->setEntityData([
                'singular' => $name,
                'plural' => $plural,
                'description' => "A {$name} entity generated with Moox Builder",
            ]);

            Log::info('Executing entity creation');

            if ($preview) {
                $this->previewTableManager
                    ->withContext($context)
                    ->setBlocks($blocks)
                    ->execute();

                Log::info('Preview table created');
            }

            $this->entityCreator->execute();

            $this->info("Entity {$name} created successfully in {$context->getContextType()} context");

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Entity creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Failed to create entity: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function createContext(
        string $entityName,
        ?string $package = null,
        bool $preview = false
    ): BuildContext {
        return ContextFactory::create(
            $this->getBuildContext($preview, $package),
            $entityName,
            $package ? ['package' => ['name' => $package]] : [],
            $this->option('preset')
        );
    }
}
