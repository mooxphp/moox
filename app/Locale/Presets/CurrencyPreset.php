<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class CurrencyPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Tabs,
            new Text(
                name: 'id',
                label: 'ID',
                description: 'Unique identifier for the currency',
            ),
            new Text(
                name: 'code',
                label: 'Code',
                description: 'ISO 4217 currency code',
                unique: true,
            ),
            new Text(
                name: 'common_name',
                label: 'Common Name',
                description: 'Common name of the currency in different languages',
            ),
            new Text(
                name: 'symbol',
                label: 'Symbol',
                description: 'Currency symbol',
            ),
            new Text(
                name: 'exonyms',
                label: 'Exonyms',
                description: 'Exonyms of the currency in different languages',
            ),
        ];
    }
}
