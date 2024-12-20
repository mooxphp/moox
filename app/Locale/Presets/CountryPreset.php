<?php

declare(strict_types=1);

namespace App\Locale\Presets;

use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Fields\KeyValue;
use Moox\Builder\Blocks\Fields\Number;
use Moox\Builder\Blocks\Fields\Text;
use Moox\Builder\Blocks\Singles\Simple;
use Moox\Builder\Presets\AbstractPreset;

class CountryPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Text(
                name: 'alpha2',
                label: 'Alpha-2 Code',
                description: 'Two-letter ISO 639-1 code',
                length: 3,
                unique: true,
                nullable: false,
            ),
            new Text(
                name: 'alpha3_b',
                label: 'Alpha-3 Code (B)',
                description: 'Three-letter ISO 639-1 code (bibliographic)',
                length: 3,
                nullable: true,
            ),
            new Text(
                name: 'alpha3_t',
                label: 'Alpha-3 Code (T)',
                description: 'Three-letter ISO 639-1 code (terminological)',
                length: 3,
                nullable: true,
            ),
            new Text(
                name: 'common_name',
                label: 'Common Name',
                description: 'Common name of the country',
                nullable: false,
            ),
            new Text(
                name: 'native_name',
                label: 'Native Name',
                description: 'Native name of the country',
                nullable: true,
            ),
            new KeyValue(
                name: 'exonyms',
                label: 'Exonyms',
                description: 'Exonyms of the country',
                nullable: true,
            ),
            new SimpleType(
                name: 'region',
                label: 'Region',
                description: 'Region of the country',
                enum: [
                    'Africa',
                    'Americas',
                    'Asia',
                    'Europe',
                    'Oceania',
                    'Antarctica',
                ],
                nullable: true,
            ),
            new SimpleType(
                name: 'subregion',
                label: 'Subregion',
                description: 'Subregion of the country',
                enum: [
                    'Northern Africa',
                    'Sub-Saharan Africa',
                    'Eastern Africa',
                    'Middle Africa',
                    'Southern Africa',
                    'Western Africa',
                    'Latin America and the Caribbean',
                    'Northern America',
                    'Caribbean',
                    'Central America',
                    'South America',
                    'Central Asia',
                    'Eastern Asia',
                    'South-Eastern Asia',
                    'Southern Asia',
                    'Western Asia',
                    'Eastern Europe',
                    'Northern Europe',
                    'Southern Europe',
                    'Western Europe',
                    'Australia and New Zealand',
                    'Melanesia',
                    'Micronesia',
                    'Polynesia',
                ],
                nullable: true,
            ),
            new Number(
                name: 'calling_code',
                label: 'Calling Code',
                max: 100,
                description: 'International calling code',
                nullable: true,
            ),
            new Text(
                name: 'capital',
                label: 'Capital',
                description: 'Capital city of the country',
                nullable: true,
            ),
            new Text(
                name: 'population',
                label: 'Population',
                description: 'Population of the country',
                nullable: true,
            ),
            new Text(
                name: 'area',
                label: 'Area',
                description: 'Area of the country in square kilometers',
                nullable: true,
            ),
            new KeyValue(
                name: 'links',
                label: 'Links',
                description: 'Links related to the country',
                nullable: true,
            ),
            new KeyValue(
                name: 'tlds',
                label: 'TLDs',
                description: 'Top-level domains of the country',
                nullable: true,
            ),
            new KeyValue(
                name: 'membership',
                label: 'Membership',
                description: 'Membership in international organizations',
                nullable: true,
            ),
            new SimpleType(
                name: 'embargo',
                label: 'Embargo',
                description: 'Embargo status',
                nullable: true,
                enum: ['New', 'Open', 'Pending', 'Closed'],
            ),
            new KeyValue(
                name: 'embargo_data',
                label: 'Embargo Data',
                description: 'Data related to embargo',
                nullable: true,
            ),
            new KeyValue(
                name: 'address_format',
                label: 'Address Format',
                description: 'Format for addresses in the country',
                nullable: true,
            ),
            new Text(
                name: 'postal_code_regex',
                label: 'Postal Code Regex',
                description: 'Regular expression for postal codes',
                nullable: true,
            ),
            new Text(
                name: 'dialing_prefix',
                label: 'Dialing Prefix',
                description: 'Dialing prefix for international calls',
                length: 10,
                nullable: true,
            ),
            new KeyValue(
                name: 'phone_number_formatting',
                label: 'Phone Number Formatting',
                description: 'Formatting for phone numbers',
                nullable: true,
            ),
            new Text(
                name: 'date_format',
                label: 'Date Format',
                description: 'Default date format',
                length: 10,
                nullable: false,
            ),
            new KeyValue(
                name: 'currency_format',
                label: 'Currency Format',
                description: 'Format for currency',
                nullable: true,
            ),
        ];
    }
}
