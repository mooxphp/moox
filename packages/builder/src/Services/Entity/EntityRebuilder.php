<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\Build\BuildManager;

class EntityRebuilder
{
    public function __construct(
        private readonly BuildManager $buildManager
    ) {}

    public function rebuild(int $entityId, ?string $buildContext = null): array
    {
        $existingBlocks = $this->getExistingBlocks($entityId);
        $this->clearBlocks($entityId);

        $blocks = $this->buildManager->reconstructFromLatest($entityId, $buildContext);

        if (empty($blocks)) {
            throw new \RuntimeException("No build found for entity {$entityId}");
        }

        return $this->restoreBlocks($entityId, $blocks, $existingBlocks);
    }

    protected function getExistingBlocks(int $entityId): \Illuminate\Support\Collection
    {
        return DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->get()
            ->keyBy('block_class')
            ->map(fn ($block) => json_decode($block->options, true));
    }

    protected function clearBlocks(int $entityId): void
    {
        DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->delete();
    }

    protected function restoreBlocks(int $entityId, array $blocks, \Illuminate\Support\Collection $existingBlocks): array
    {
        foreach ($blocks as $index => $block) {
            $this->restoreBlock($entityId, $block, $existingBlocks, $index);
        }

        return $blocks;
    }

    protected function restoreBlock(int $entityId, object $block, \Illuminate\Support\Collection $existingBlocks, int $index): void
    {
        if ($existingBlocks->has(get_class($block))) {
            $this->applyExistingOptions($block, $existingBlocks->get(get_class($block)));
        }

        $this->saveBlock($entityId, $block, $index);
    }

    protected function applyExistingOptions(object $block, array $options): void
    {
        foreach ($options as $key => $value) {
            $setter = 'set'.ucfirst($key);
            if (method_exists($block, $setter)) {
                $block->$setter($value);
            }
        }
    }

    protected function saveBlock(int $entityId, object $block, int $index): void
    {
        DB::table('builder_entity_blocks')->insert([
            'entity_id' => $entityId,
            'title' => $block->getTitle(),
            'description' => $block->getDescription(),
            'block_class' => get_class($block),
            'options' => json_encode($block->getOptions()),
            'sort_order' => $index,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
