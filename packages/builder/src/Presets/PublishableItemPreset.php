<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\MarkdownEditor;
use Moox\Builder\Blocks\Publish;
use Moox\Builder\Blocks\Tabs;
use Moox\Builder\Blocks\TitleWithSlug;

class PublishableItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new TitleWithSlug(
                titleFieldName: 'title',
                slugFieldName: 'slug',
                label: 'Title',
                description: 'The title of the item',
                nullable: false
            ),
            new Publish,
            new Tabs,
            new MarkdownEditor(
                name: 'content',
                label: 'Content',
                description: 'The content of the item',
            ),
        ];
    }
}
