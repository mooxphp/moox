<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Fields\KeyValue;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class StaticLanguagePreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Text(
                name: 'alpha2',
                label: 'Alpha-2 Code',
                description: 'Two-letter ISO 639-1 code',
                unique: true,
                nullable: false,
            ),
            new Text(
                name: 'alpha3_b',
                label: 'Alpha-3 Bibliographic Code',
                description: 'Three-letter ISO 639-2 code for bibliographic use',
                nullable: true,
            ),
            new Text(
                name: 'alpha3_t',
                label: 'Alpha-3 Terminology Code',
                description: 'Three-letter ISO 639-2 code for terminology use',
                nullable: true,
            ),
            new Text(
                name: 'common_name',
                label: 'Common Name',
                description: 'Common name of the language',
                nullable: false,
            ),
            new Text(
                name: 'native_name',
                label: 'Native Name',
                description: 'Name of the language in its native script',
                nullable: true,
            ),
            new SimpleType(
                name: 'script',
                label: 'Script',
                description: 'Script used by the language',
                enum: ['Latin', 'Cyrillic', 'Arabic', 'Devanagari', 'Other'],
                nullable: false,
            ),
            new SimpleType(
                name: 'direction',
                label: 'Direction',
                description: 'Direction of the script',
                enum: ['LTR', 'RTL'],
                nullable: false,
            ),
            new KeyValue(
                name: 'exonyms',
                label: 'Exonyms',
                description: 'Names of the language in other languages',
                nullable: true,
            ),

        ];
    }
}
