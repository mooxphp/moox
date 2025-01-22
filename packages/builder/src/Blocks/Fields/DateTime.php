<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class DateTime extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $sortable = false,
        protected bool $withSeconds = false,
        protected string $type = 'datetime', // datetime, date, time
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $componentClass = match ($this->type) {
            'date' => 'DatePicker',
            'time' => 'TimePicker',
            default => 'DateTimePicker',
        };

        $filterClass = match ($this->type) {
            'date' => 'DateFilter',
            'time' => 'TimeFilter',
            default => 'DateTimeFilter',
        };

        $this->useStatements = [
            'resource' => [
                'forms' => [sprintf('use Filament\Forms\Components\%s;', $componentClass)],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => [sprintf('use Filament\Tables\Filters\%s;', $filterClass)],
            ],
        ];

        $this->formFields['resource'] = [
            "{$componentClass}::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()')
                .($this->withSeconds && $this->type !== 'date' ? '->withSeconds()' : ''),
        ];

        $this->tableColumns['resource'] = [
            sprintf("TextColumn::make('%s')", $this->name)
                .'->{'.$this->type.'}()'
                .($this->sortable ? '->sortable()' : ''),
        ];

        $this->filters['resource'] = [
            sprintf("%s::make('%s')", $filterClass, $this->name),
        ];

        $this->migrations['fields'] = [
            sprintf("\$table->%s('%s')", $this->type, $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => match ($this->type) {
                'date' => 'fake()->date()',
                'time' => 'fake()->time()',
                default => 'fake()->dateTime()',
            },
        ];
    }
}
