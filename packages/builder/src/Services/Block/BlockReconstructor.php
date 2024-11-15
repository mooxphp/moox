<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Block;

class BlockReconstructor
{
    public function __construct(
        private readonly BlockFactory $blockFactory
    ) {}

    public function reconstruct(object $build): array
    {
        $blockData = json_decode($build->data, true);
        $blocks = [];

        foreach ($blockData as $data) {
            $block = $this->reconstructBlock($data, $build->entity_id);
            if ($block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    protected function reconstructBlock(array $data, int $entityId): ?object
    {
        $block = $this->blockFactory->createFromBuild($data['type'], $entityId);

        if (! $block) {
            return null;
        }

        $block->initialize();
        $block->setUseStatements($data['useStatements']);
        $block->setArrayData($data);

        return $block;
    }
}
