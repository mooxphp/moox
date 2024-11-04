<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Text;
use Moox\Builder\Blocks\TextArea;

class SimpleItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Text(
                name: 'title',
                label: 'Title',
                description: 'The title of the item',
                length: 255,
                nullable: false,
                unique: true,
                searchable: true,
                sortable: true
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item'
            ),
        ];

        $this->features = [];
    }
}
