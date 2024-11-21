<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Block;

use RuntimeException;

class BlockReconstructor
{
    public function __construct(
        private readonly BlockFactory $blockFactory
    ) {}

    public function reconstruct(object $build): array
    {
        $blockData = json_decode($build->data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'JSON decode error: '.json_last_error_msg().
                ' Raw data: '.$build->data
            );
        }

        if (! is_array($blockData)) {
            throw new RuntimeException(
                'Block data is not an array. Type: '.gettype($blockData).
                ' Raw data: '.$build->data
            );
        }

        $blocks = [];
        foreach ($blockData as $data) {
            try {
                $block = $this->reconstructBlock($data, $build->entity_id);
                if ($block) {
                    $blocks[] = $block;
                }
            } catch (\Exception $e) {
                throw new RuntimeException(
                    'Block reconstruction failed: '.$e->getMessage().
                    ' Data: '.json_encode($data)
                );
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

        if (isset($data['useStatements'])) {
            $block->setUseStatements($data['useStatements']);
        }

        if (isset($data['options'])) {
            $block->setArrayData($data);
        }

        return $block;
    }
}
