<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Fields\Boolean;
use Moox\Builder\Blocks\Fields\Relationship;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Presets\AbstractPreset;

class PivotPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Text(
                name: 'id',
                label: 'ID',
                description: 'Unique identifier for the language',
            ),
            new Text(
                name: 'country_id',
                label: 'Country ID',
                description: 'ID of the country',
                unique: true,
            ),
            new Text(
                name: 'currency_id',
                label: 'Currency ID',
                description: 'ID of the Currency',
                unique: true,
            ),
            new Boolean(
                name: 'is_primary',
                label: 'Primary',
                description: 'If Currency is primary in country',
                default: false,
            ),
            new Relationship(
                name: 'currency',
                label: 'currency',
                description: 'Currency to Country',
                nullable: false,
                relatedModel: \App\Locale\Models\StaticLanguage::class,
            ),
            new Relationship(
                name: 'country',
                label: 'country',
                description: 'Currency to Country',
                nullable: false,
                // relatedModel: \App\Locale\Locale\Models\Country::class,
            ),
        ];
    }
}
