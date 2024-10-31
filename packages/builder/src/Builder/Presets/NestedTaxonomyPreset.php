<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Presets;

use Moox\Builder\Builder\Blocks\Text;
use Moox\Builder\Builder\Blocks\TextArea;
use Moox\Builder\Builder\Features\NestedTaxonomy;

class NestedTaxonomyPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Text(
                name: 'title',
                label: 'Title',
                description: 'The title of the taxonomy',
                length: 255,
                nullable: false,
                unique: true,
                searchable: true,
                sortable: true
            ),
            new Text(
                name: 'slug',
                label: 'Slug',
                description: 'The URL slug for the taxonomy',
                length: 255,
                nullable: false,
                unique: true
            ),
            new TextArea(
                name: 'description',
                label: 'Description',
                description: 'The description of the taxonomy'
            ),
        ];

        $this->features = [
            // TODO: Implement NestedTaxonomy feature
            //new NestedTaxonomy,
        ];
    }
}
