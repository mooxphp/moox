<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Features\SimpleStatus;
use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Fields\TextArea;
use Moox\Builder\Blocks\Sections\SimpleAddressSection;
use Moox\Builder\Blocks\Singles\SoftDelete;

class SoftDeleteItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Text(
                name: 'title',
                label: 'Title',
                description: 'The title of the item',
                nullable: true,
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item'
            ),
            new Text(
                name: 'keks',
                label: 'Keks',
                description: 'For my lovely wife',
            ),
            new Tabs,
            new SimpleAddressSection,
            new SimpleType(
                enum: ['Post', 'Page'],
            ),
            new SimpleStatus(
                enum: ['Probably', 'Never', 'Done', 'Maybe'],
            ),
            new SoftDelete,
        ];
    }
}
