<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class ColorPicker extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\ColorPicker;'],
                'columns' => ['use Filament\Tables\Columns\ColorColumn;'],
                'filters' => ['use Filament\Tables\Filters\TextFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "ColorPicker::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "ColorColumn::make('{$this->name}')",
        ];

        $this->filters['resource'] = [
            "TextFilter::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->name}', 7)"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->hexColor()',
        ];
    }
}
