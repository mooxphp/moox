<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Features\SimpleStatus;
use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Fields\TextArea;
use Moox\Builder\Blocks\Sections\SimpleAddressSection;
use Moox\Builder\Blocks\Singles\Simple;

class FullItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Text(
                name: 'title',
                label: 'Title',
                description: 'The title of the item',
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item',
                nullable: true,
            ),
            new Tabs,
            new SimpleAddressSection,
            new SimpleStatus(
                enum: ['Probably', 'Never', 'Done', 'Maybe'],
            ),
            new SimpleType(
                enum: ['Post', 'Page'],
            ),
        ];
    }
}
