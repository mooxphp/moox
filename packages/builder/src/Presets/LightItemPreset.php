<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Features\SimpleStatus;
use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Filament\TextArea;
use Moox\Builder\Blocks\Moox\TitleWithSlug;
use Moox\Builder\Blocks\Sections\AddressSection;
use Moox\Builder\Blocks\Singles\Light;

class LightItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Light,
            new TitleWithSlug(
                titleFieldName: 'title',
                slugFieldName: 'slug',
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item'
            ),
            new AddressSection,
            new SimpleStatus(
                enum: ['Probably', 'Never', 'Done', 'Maybe'],
            ),
            new SimpleType(
                enum: ['Post', 'Page'],
            ),
        ];
    }
}
