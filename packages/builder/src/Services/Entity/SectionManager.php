<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Blocks\AbstractBlock;

final class SectionManager
{
    private array $sections = [];

    private array $metaSections = [];

    public function addBlock(AbstractBlock $block): void
    {
        if (! $block->hasSection()) {
            $sectionName = 'default';
        } else {
            $sectionName = $block->getSectionName();
        }

        $order = $block->getSectionOrder();

        if ($block->isMetaSection()) {
            if (! isset($this->metaSections[$sectionName])) {
                $this->metaSections[$sectionName] = [];
            }
            $this->metaSections[$sectionName][$order][] = $block;
        } else {
            if (! isset($this->sections[$sectionName])) {
                $this->sections[$sectionName] = [];
            }
            $this->sections[$sectionName][$order][] = $block;
        }
    }

    private function formatSections(array $sections): string
    {
        if (empty($sections)) {
            return '';
        }

        $output = [];
        foreach ($sections as $sectionName => $orderBlocks) {
            ksort($orderBlocks);
            $formattedBlocks = [];

            foreach ($orderBlocks as $blocks) {
                foreach ($blocks as $block) {
                    if (isset($block->getFormFields()['resource'])) {
                        $formattedBlocks = array_merge($formattedBlocks, $block->getFormFields()['resource']);
                    }
                }
            }

            if (! empty($formattedBlocks)) {
                if ($sectionName === 'default') {
                    $output[] = implode(',', $formattedBlocks);
                } else {
                    $output[] = "Section::make('".$sectionName."')->schema([".implode(',', $formattedBlocks).'])';
                }
            }
        }

        return implode(',', $output);
    }

    public function getFormattedSections(): array
    {
        return [
            'form_sections' => $this->formatSections($this->sections),
            'meta_sections' => $this->formatSections($this->metaSections),
        ];
    }
}
