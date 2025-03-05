<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Features;

use Moox\Builder\Blocks\AbstractBlock;

class SimpleStatus extends AbstractBlock
{
    public function __construct(
        string $name = 'status',
        string $label = 'Status',
        string $description = 'Adds a simple status field based on an enum to a resource',
        protected array $enum = ['New', 'Open', 'Pending', 'Closed'],
        protected bool $nullable = false,
        protected bool $sortable = true,
        protected bool $searchable = true,
        protected bool $toggleable = true,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements['resource'] = [
            'forms' => ['use Filament\Forms\Components\Select;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => [
                'use Filament\Tables\Filters\SelectFilter;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
        ];

        $options = '['.implode(', ', array_map(fn ($value): string => sprintf("'%s' => '%s'", $value, $value), $this->enum)).']';

        $this->addSection('status')
            ->asMeta()
            ->hideHeader()
            ->withFields([
                "Select::make('".$this->name.'\')
                    ->label(\''.$this->label.'\')
                    ->placeholder(__(\'core::core.status\'))
                    ->options('.$options.')
                    '.($this->nullable ? '' : '->required()'),
            ]);

        $this->tableColumns['resource'] = [
            sprintf("TextColumn::make('%s')", $this->name)
                .($this->sortable ? '->sortable()' : '')
                .($this->searchable ? '->searchable()' : '')
                .($this->toggleable ? '->toggleable()' : ''),
        ];

        $this->migrations['fields'] = [
            sprintf("\$table->enum('%s', ['", $this->name).implode("', '", $enum)."'])"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->filters['resource'] = [
            "SelectFilter::make('{$this->name}')
                ->label('{$this->label}')
                ->placeholder(__('core::core.filter').' {$this->label}')
                ->options({$options})",
        ];

        $this->config['tabs'] = [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [],
            ],
            ...array_map(fn ($status): array => [
                'label' => $status,
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => $status,
                    ],
                ],
            ], $this->enum),
        ];
    }

    public function getTabs(): array
    {
        return $this->config['tabs'];
    }
}
