<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Contexts\ContextFactory;
use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Build\BuildStateManager;
use Moox\Builder\Services\Entity\EntityCreator;
use Moox\Builder\Services\Preview\PreviewTableManager;
use Override;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create-entity
        {name : The name of the entity}
        {--context= : Context to use (app, moox, package, preview)}
        {--package= : Package namespace}
        {--preview : Generate in preview mode}
        {--app : Generate in app context}
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

            if ($blocks === []) {
                $this->error(sprintf("Preset '%s' returned no blocks", $presetName));

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
                'description' => sprintf('A %s entity generated with Moox Builder', $name),
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

            $this->info(sprintf('Entity %s created successfully in %s context', $name, $context->getContextType()));

            return self::SUCCESS;
        } catch (Exception $exception) {
            Log::error('Entity creation failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            $this->error('Failed to create entity: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    #[Override]
    protected function createContext(
        string $entityName,
        ?string $package = null,
        bool $preview = false,
        bool $app = false
    ): BuildContext {
        return ContextFactory::create(
            $this->getBuildContext($preview, $app, $package),
            $entityName,
            $package ? ['package' => ['name' => $package]] : [],
            $this->option('preset')
        );
    }

    #[Override]
    protected function getBuildContext(?bool $preview = false, ?bool $app = false, ?string $package = null): string
    {
        if ($context = $this->option('context')) {
            return $context;
        }

        if ($app) {
            return 'app';
        }

        if ($package) {
            return 'package';
        }

        if ($preview) {
            return 'preview';
        }

        $contexts = array_keys(config('builder.contexts', []));

        if (count($contexts) === 1) {
            return reset($contexts);
        }

        return $this->choice('Choose a context', $contexts);
    }
}
