<?php

declare(strict_types=1);

namespace App\Builder\Presets;

use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Fields\Relationship;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class LocalePreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Tabs,
            new Text(
                name: 'id',
                label: 'ID',
                description: 'Unique identifier for the language',
            ),
            new Text(
                name: 'language_id',
                label: 'Language ID',
                description: 'ID of the language',
                unique: true,
            ),
            new Text(
                name: 'country_id',
                label: 'Country ID',
                description: 'ID of the country',
                unique: true,
            ),
            new Text(
                name: 'locale',
                label: 'Locale',
                description: 'Locale of the language',
                unique: true,
            ),
            new Text(
                name: 'name',
                label: 'Name',
                description: 'Name of the language',
                unique: true,
            ),
            new Relationship(
                name: 'language',
                label: 'language',
                description: 'Language to Locale',
                nullable: false,
                relatedModel: \App\Builder\Locale\Models\StaticLanguage::class,
            ),
            new Relationship(
                name: 'country',
                label: 'country',
                description: 'Country to Locale',
                nullable: false,
                // relatedModel: \App\Builder\Locale\Models\Country::class,
            ),
        ];
    }
}
