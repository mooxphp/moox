<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class SimpleStatus extends AbstractBlock
{
    private bool $sortable;

    private bool $searchable;

    private bool $toggleable;

    public function __construct(
        string $name = 'status',
        string $label = 'Status',
        string $description = 'Adds a simple status field based on an enum to a resource',
        array $enum = ['New', 'Open', 'Pending', 'Closed', 'Rejected', 'Cancelled'],
        bool $nullable = false,
        bool $sortable = true,
        bool $searchable = true,
        bool $toggleable = true,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->sortable = $sortable;
        $this->searchable = $searchable;
        $this->toggleable = $toggleable;

        $this->useStatements['resource'] = [
            'forms' => ['use Filament\Forms\Components\Select;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => [
                'use Filament\Tables\Filters\Filter;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
        ];

        $options = '["'.implode('", "', $enum).'"]';

        $this->addSection('status')
            ->asMeta()
            ->hideHeader()
            ->withFields([
                'Select::make(\''.$this->name.'\')
                    ->label(\''.$this->label.'\')
                    ->placeholder(__(\'core::core.status\'))
                    ->options('.$options.')',
            ]);

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')"
                .($this->sortable ? '->sortable()' : '')
                .($this->searchable ? '->searchable()' : '')
                .($this->toggleable ? '->toggleable()' : ''),
        ];

        $this->migrations['fields'] = [
            "\$table->enum('{$this->name}', ['".implode("', '", $enum)."'])"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->filters['resource'] = [
            "Filter::make('{$this->name}')
                ->form([
                    Select::make('{$this->name}')
                        ->label('{$this->label}')
                        ->placeholder(__('core::core.search'))
                        ->options({$options})
                ])",
        ];
    }
}
