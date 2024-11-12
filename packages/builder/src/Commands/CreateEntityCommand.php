<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\EntityGenerator;
use Moox\Builder\Services\EntityService;

class CreateEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:create {name} {--package=} {--preview} {--app} {--preset=}';

    protected $description = 'Create a new entity with model, resource and plugin';

    public function __construct(
        private readonly EntityService $entityService,
        private readonly EntityGenerator $entityGenerator
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $name = $this->argument('name');
        $package = $this->option('package');
        $preview = $this->option('preview');
        $buildContext = $preview ? 'preview' : ($package ? 'package' : 'app');

        $presetName = $this->option('preset');
        if (! $presetName) {
            $presetName = $this->choice(
                'Which preset would you like to use?',
                [
                    'simple-item',
                    'publishable-item',
                    'full-item',
                    'simple-taxonomy',
                    'nested-taxonomy',
                ],
                'simple-item'
            );
        }

        $result = $this->entityService->create($name, $buildContext, $presetName);
        $entity = $result['entity'];

        if ($result['status'] === 'exists') {
            if (! $this->confirm("Entity '{$name}' already exists in {$buildContext} context. Do you want to rebuild it?")) {
                return;
            }

            $latestBuild = $this->entityService->getLatestBuild($entity->id);
            if ($latestBuild) {
                $blocks = $this->entityService->reconstructBlocksFromBuild($latestBuild);
            } else {
                $preset = PresetRegistry::getPreset($presetName);
                $blocks = $preset->getBlocks();
            }
        } else {
            $preset = PresetRegistry::getPreset($presetName);
            $blocks = $preset->getBlocks();
        }

        $context = $this->createContext($name, $package, $preview);
        $context->setPresetName($presetName);

        $this->entityGenerator->setContext($context);
        $this->entityGenerator->setBlocks($blocks);
        $this->entityGenerator->execute();

        if ($preview) {
            $this->entityService->createPreviewTable($name, $blocks);
        }

        $this->entityService->recordBuild(
            $entity->id,
            $buildContext,
            $blocks,
            $this->entityGenerator->getGeneratedFiles()
        );

        $this->info('Entity '.$name.' '.($result['status'] === 'exists' ? 're' : '').'built successfully in '.$buildContext);
    }
}
