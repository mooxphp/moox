<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\DB;
use Moox\Builder\PresetRegistry;
use Moox\Builder\Services\Block\BlockFactory;

class EntityRebuilder
{
    public function __construct(
        private readonly BlockFactory $blockFactory
    ) {}

    public function rebuild(int $entityId, string $presetName): array
    {
        $existingBlocks = $this->getExistingBlocks($entityId);
        $this->clearBlocks($entityId);

        $preset = PresetRegistry::getPreset($presetName);
        $blocks = $preset->getBlocks();

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
        return collect($blocks)
            ->map(function ($block, $index) use ($entityId, $existingBlocks) {
                $blockClass = get_class($block);
                if ($existingOptions = $existingBlocks[$blockClass] ?? null) {
                    $this->restoreBlockOptions($block, $existingOptions);
                }

                $this->saveBlock($entityId, $block, $index);

                return $block;
            })
            ->all();
    }

    protected function restoreBlockOptions(object $block, array $options): void
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
