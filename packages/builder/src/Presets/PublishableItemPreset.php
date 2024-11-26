<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Publish;
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
        ];
    }
}
