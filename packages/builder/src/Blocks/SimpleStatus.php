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
        array $enum = ['New', 'Open', 'Pending', 'Closed'],
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

        $options = '['.implode(', ', array_map(fn ($value) => "'$value' => '$value'", $enum)).']';

        $this->addSection('status')
            ->asMeta()
            ->hideHeader()
            ->withFields([
                'Select::make(\''.$this->name.'\')
                    ->label(\''.$this->label.'\')
                    ->placeholder(__(\'core::core.status\'))
                    ->options('.$options.')
                    '.($this->nullable ? '' : '->required()'),
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

        $this->config['tabs'] = [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [],
            ],
            'probably' => [
                'label' => 'Probably',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => 'Probably',
                    ],
                ],
            ],
            'never' => [
                'label' => 'Never',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => 'Never',
                    ],
                ],
            ],
            'done' => [
                'label' => 'Done',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => 'Done',
                    ],
                ],
            ],
            'maybe' => [
                'label' => 'Maybe',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => 'Maybe',
                    ],
                ],
            ],
        ];
    }

    public function getTabs(): array
    {
        return $this->config['tabs'];
    }
}
