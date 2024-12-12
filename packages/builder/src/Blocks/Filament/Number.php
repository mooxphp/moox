<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class Number extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $isFloat = false,
        protected ?int $min = null,
        protected ?int $max = null,
        protected ?float $step = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\TextInput;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\NumberFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "TextInput::make('{$this->name}')
                ->label('{$this->label}')
                ->numeric()"
                .($this->nullable ? '' : '->required()')
                .($this->min !== null ? "->minValue({$this->min})" : '')
                .($this->max !== null ? "->maxValue({$this->max})" : '')
                .($this->step !== null ? "->step({$this->step})" : ''),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->numeric(".($this->isFloat ? '2' : '0').')',
        ];

        $this->filters['resource'] = [
            "NumberFilter::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->isFloat ? 'float' : 'integer')."('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => $this->isFloat ? 'fake()->randomFloat(2)' : 'fake()->randomNumber()',
        ];
    }
}
