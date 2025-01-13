<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Fields\Relationship;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class CountryTimezonePreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Relationship(
                name: 'country_id',
                label: 'country',
                description: 'Country to Timezone',
                nullable: false,
                relatedModel: \App\Locale\Models\StaticCountry::class,
            ),
            new Relationship(
                name: 'timezone_id',
                label: 'timezone',
                description: 'Timezone to Country',
                nullable: false,
                relatedModel: \App\Locale\Models\StaticTimezone::class,
            ),
        ];
    }
}
