<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Sections;

use Moox\Builder\Blocks\AbstractBlock;

final class SimpleAddressSection extends AbstractBlock
{
    public function __construct(
        string $name = 'address_section',
        string $label = 'Address',
        string $description = 'Address section with street, city, postal code and country',
        bool $nullable = true,
        protected int $length = 255,
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

        $this->migrations['fields'] = [
            "\$table->string('street', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
            "\$table->string('city', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
            "\$table->string('postal_code', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
            "\$table->string('country', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
        ];
    }

    public function getFillableFields(): array
    {
        return [
            'street',
            'city',
            'postal_code',
            'country',
        ];
    }
}
