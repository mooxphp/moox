<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;
use RuntimeException;

class EntityCreator extends AbstractEntityService
{
    protected array $entityData = [];

    protected array $blocks = [];

    public function __construct(
        private readonly EntityGenerator $entityGenerator,
        private readonly BuildManager $buildManager,
    ) {}

    public function setBlocks(array $blocks): void
    {
        if ($blocks === []) {
            throw new RuntimeException('Blocks array cannot be empty');
        }

        foreach ($blocks as $block) {
            if (! method_exists($block, 'getOptions') || ! method_exists($block, 'getMigrations')) {
                throw new RuntimeException('Invalid block object: missing required methods');
            }
        }

        $this->blocks = $blocks;
        $this->entityGenerator->setBlocks($blocks);
    }

    public function setEntityData(array $data): void
    {
        $this->entityData = $data;
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();

        $entityId = $this->createOrUpdateEntity();
        $contextType = $this->context->getContextType();

        $this->entityGenerator->setContext($this->context);
        $this->entityGenerator->execute();

        $generatedData = $this->entityGenerator->getGenerationResult();

        if (! isset($generatedData['blocks'])) {
            throw new RuntimeException('Generator did not return blocks. Debug: '.print_r($generatedData, true));
        }

        $this->blocks = $generatedData['blocks'];

        if ($this->blocks === []) {
            throw new RuntimeException('Blocks array empty after generation. Debug: '.print_r($this->blocks, true));
        }

        $this->buildManager->setContext($this->context);
        $this->buildManager->recordBuild(
            $entityId,
            $contextType,
            $this->blocks,
            $generatedData['files'] ?? []
        );
    }

    protected function createOrUpdateEntity(): int
    {
        $name = $this->context->getEntityName();
        $existingEntity = DB::table('builder_entities')
            ->where('singular', $name)
            ->first();

        if ($existingEntity) {
            return $existingEntity->id;
        }

        return DB::table('builder_entities')->insertGetId([
            'singular' => $name,
            'plural' => $this->entityData['plural'] ?? $name,
            'description' => $this->entityData['description'] ?? null,
            'preset' => $this->entityData['preset'] ?? 'simple-item',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
