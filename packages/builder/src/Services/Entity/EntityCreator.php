<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Build\BuildManager;

class EntityCreator extends ContextAwareService
{
    public function __construct(
        private readonly EntityRebuilder $entityRebuilder,
        private readonly BuildManager $buildManager,
        private readonly EntityGenerator $entityGenerator,
    ) {
        parent::__construct();
    }

    public function createFromPreset(string $name, string $buildContext, string $presetName): array
    {
        $preset = PresetRegistry::getPreset($presetName);

        return $this->create($name, $buildContext, $preset->getBlocks(), $presetName);
    }

    public function createFromBlocks(string $name, string $buildContext, array $blocks): array
    {
        return $this->create($name, $buildContext, $blocks);
    }

    protected function create(string $name, string $buildContext, array $blocks, ?string $presetName = null): array
    {
        $existingEntity = $this->findEntity($name);

        if ($existingEntity) {
            $this->deactivateExistingBuildsForContext($existingEntity->id, $buildContext);
            $blocks = $this->entityRebuilder->rebuild($existingEntity->id);
            $entityId = $existingEntity->id;
        } else {
            $entityId = $this->createEntity($name, $buildContext, $presetName);
        }

        $this->entityGenerator->setContext($this->context);
        $this->entityGenerator->setBlocks($blocks);
        $this->entityGenerator->execute();

        $generatedFiles = $this->entityGenerator->getGeneratedFiles();
        $filesToRecord = $buildContext === 'preview'
            ? array_keys($generatedFiles)  // Only store paths in preview
            : $generatedFiles;             // Store full file data otherwise

        $this->buildManager->recordBuild($entityId, $buildContext, $blocks, $filesToRecord);

        return [
            'entity' => DB::table('builder_entities')->find($entityId),
            'status' => 'created',
            'blocks' => $blocks,
            'files' => $filesToRecord,
        ];
    }

    protected function findEntity(string $name): ?object
    {
        return DB::table('builder_entities')
            ->where('singular', $name)
            ->whereNull('deleted_at')
            ->first();
    }

    protected function deactivateExistingBuildsForContext(int $entityId, string $buildContext): void
    {
        DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $buildContext)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    protected function createEntity(string $name, string $buildContext, ?string $presetName = null): int
    {
        $entityId = DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => \Illuminate\Support\Str::plural($name),
            'description' => '',
            'preset' => $presetName ?? '',
            'relations' => json_encode([]),
            'taxonomies' => json_encode([]),
            'last_built_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $entityId;
    }

    public function execute(): void
    {
        // Not used in this service, but required by ContextAwareService
    }
}
