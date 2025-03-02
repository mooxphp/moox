<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

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
                .($this->min !== null ? sprintf('->minValue(%d)', $this->min) : '')
                .($this->max !== null ? sprintf('->maxValue(%d)', $this->max) : '')
                .($this->step !== null ? sprintf('->step(%s)', $this->step) : ''),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->numeric(".($this->isFloat ? '2' : '0').')',
        ];

        $this->filters['resource'] = [
            sprintf("NumberFilter::make('%s')", $this->name),
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->isFloat ? 'float' : 'integer').sprintf("('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => $this->isFloat ? 'fake()->randomFloat(2)' : 'fake()->randomNumber()',
        ];
    }
}
