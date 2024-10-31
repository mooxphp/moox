<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Presets;

use Moox\Builder\Builder\Blocks\Text;
use Moox\Builder\Builder\Blocks\TextArea;
use Moox\Builder\Builder\Features\Publish;
use Moox\Builder\Builder\Features\SoftDelete;

class PublishableItemPreset extends AbstractPreset
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
            new Text(
                name: 'slug',
                label: 'Slug',
                description: 'The URL slug for the item',
                length: 255,
                nullable: false,
                unique: true
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item'
            ),
        ];

        $this->features = [
            new Publish,
            new SoftDelete,
        ];
    }
}
