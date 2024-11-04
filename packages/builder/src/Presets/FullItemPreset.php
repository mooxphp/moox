<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Author;
use Moox\Builder\Blocks\FileUpload;
use Moox\Builder\Blocks\Publish;
use Moox\Builder\Blocks\SoftDelete;
use Moox\Builder\Blocks\Text;
use Moox\Builder\Blocks\TextArea;

class FullItemPreset extends AbstractPreset
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
            new FileUpload(
                name: 'featured_image_url',
                label: 'Featured Image',
                description: 'The featured image for the item',
                acceptedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
                directory: 'images'
            ),
        ];

        $this->features = [
            new Author,
            new Publish,
            new SoftDelete,
        ];
    }
}
