<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Moox\Builder\Blocks\AbstractBlock;

final class SectionManager
{
    private array $sections = [];

    private array $metaSections = [];

    private array $defaultFields = [];

    private array $defaultMetaFields = [];

    public function addBlock(AbstractBlock $block): void
    {
        if ($block->hasSection()) {
            $sectionName = $block->getSectionName();
            $fields = $block->getFormFields();

            if ($block->isMetaSection()) {
                if (! isset($this->metaSections[$sectionName])) {
                    $this->metaSections[$sectionName] = [];
                }
                $this->metaSections[$sectionName] = array_merge(
                    $this->metaSections[$sectionName],
                    $fields
                );
            } else {
                if (! isset($this->sections[$sectionName])) {
                    $this->sections[$sectionName] = [];
                }
                $this->sections[$sectionName] = array_merge(
                    $this->sections[$sectionName],
                    $fields
                );
            }

            return;
        }

        if ($block->isMetaSection()) {
            $this->defaultMetaFields = array_merge(
                $this->defaultMetaFields,
                $block->getFormFields()
            );
        } else {
            $this->defaultFields = array_merge(
                $this->defaultFields,
                $block->getFormFields()
            );
        }
    }

    public function formatSections(array $sections): string
    {
        if (empty($sections)) {
            return '';
        }

        $output = [];
        foreach ($sections as $section) {
            if ($section['name'] === 'taxonomy') {
                $sectionTitle = $section['hideHeader'] ?? false ? "''" : "'Taxonomy'";
                $output[] = "                    Section::make({$sectionTitle})
                        ->schema(static::getTaxonomyFields())";

                continue;
            }

            $fields = array_map(function ($field) {
                return "                        {$field},";
            }, $section['fields']);

            $sectionTitle = $section['hideHeader'] ?? false ? "''" : "'".ucfirst($section['name'])."'";

            $output[] = "                    Section::make({$sectionTitle})
                        ->schema([
".implode("\n", $fields).'
                        ]),';
        }

        return implode("\n                    ", $output);
    }

    public function getFormattedSections(): array
    {
        return [
            'form_schema' => implode(",\n", $this->defaultFields),
            'meta_schema' => implode(",\n", $this->defaultMetaFields),
            'form_sections' => $this->formatSections($this->sections),
            'meta_sections' => $this->formatSections($this->metaSections),
        ];
    }
}
