<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Sections;

use Moox\Builder\Blocks\AbstractBlock;

final class AddressSection extends AbstractBlock
{
    public function __construct(
        string $name = 'address_section',
        string $label = 'Address',
        string $description = 'Address section with street, city, postal code and country',
        bool $nullable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\TextInput;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->addSection('address', 10)
            ->withFields([
                "TextInput::make('street')",
                "TextInput::make('city')",
                "TextInput::make('postal_code')",
                "TextInput::make('country')",
            ]);
    }
}
