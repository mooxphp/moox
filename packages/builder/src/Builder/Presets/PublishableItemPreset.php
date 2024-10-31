<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Presets;

use Moox\Builder\Builder\Blocks\TitleWithSlug;
use Moox\Builder\Builder\Features\Publish;
use Moox\Builder\Builder\Features\SoftDelete;

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
        ];

        $this->features = [
            //new Publish,
            //new SoftDelete,
        ];
    }
}
