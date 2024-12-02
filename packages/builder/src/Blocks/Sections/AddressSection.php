<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Sections;

use Moox\Builder\Blocks\AbstractBlock;

final class AddressSection extends AbstractBlock
{
    protected array $formFields = [];

    protected array $tableColumns = [];

    protected array $filters = [];

    protected array $actions = [];

    protected array $factories = [];

    public function __construct(
        string $name = 'address_section',
        string $label = 'Address',
        string $description = 'Address section with street, city, postal code and country',
        bool $nullable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->setSection('address', 10);

        $this->useStatements = [
            'resource' => [
                'forms' => [
                    'use Filament\Forms\Components\Section;',
                    'use Filament\Forms\Components\TextInput;',
                ],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->formFields['resource'] = [
            "Section::make('Address')->schema([
                TextInput::make('street')->required(),
                TextInput::make('city')->required(),
                TextInput::make('postal_code')->required(),
                TextInput::make('country')->required()
            ])",
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('city')->sortable()->searchable()",
            "TextColumn::make('country')->sortable()->searchable()",
        ];

        $this->migrations = [
            'fields' => [
                'street' => '$table->string("street")',
                'city' => '$table->string("city")',
                'postal_code' => '$table->string("postal_code")',
                'country' => '$table->string("country")',
            ],
        ];

        $this->factories['model']['definitions'] = [
            'street' => 'fake()->streetAddress()',
            'city' => 'fake()->city()',
            'postal_code' => 'fake()->postcode()',
            'country' => 'fake()->country()',
        ];
    }
}
