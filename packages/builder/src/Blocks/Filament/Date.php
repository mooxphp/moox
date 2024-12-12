<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class Date extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $sortable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\DatePicker;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\DateFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "DatePicker::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->date()"
                .($this->sortable ? '->sortable()' : ''),
        ];

        $this->filters['resource'] = [
            "DateFilter::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            "\$table->date('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->date()',
        ];
    }
}
