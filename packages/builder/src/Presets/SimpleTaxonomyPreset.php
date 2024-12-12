<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Filament\Text;
use Moox\Builder\Blocks\Filament\TextArea;

class SimpleTaxonomyPreset extends AbstractPreset
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
    }
}
