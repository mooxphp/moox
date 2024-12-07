<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Features;

use Moox\Builder\Blocks\AbstractBlock;

class SimpleType extends AbstractBlock
{
    public function __construct(
        string $name = 'type',
        string $label = 'Type',
        string $description = 'Adds a simple feature to a resource',
        protected bool $nullable = false,
        protected bool $sortable = true,
        protected bool $searchable = true,
        protected bool $toggleable = true,
        protected array $enum = ['Article', 'Quote', 'Video', 'Note'],
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $options = '['.implode(', ', array_map(fn ($value) => "'$value' => '$value'", $this->enum)).']';

        $this->useStatements['resource'] = [
            'forms' => ['use Filament\Forms\Components\Select;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => [
                'use Filament\Tables\Filters\SelectFilter;',
                'use Illuminate\Database\Eloquent\Builder;',
            ],
        ];

        $this->addSection('type')
            ->asMeta()
            ->hideHeader()
            ->withFields([
                'Select::make(\''.$this->name.'\')
                    ->label(\''.$this->label.'\')
                    ->placeholder(__(\'core::core.type\'))
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
            ...array_map(fn ($type) => [
                'label' => $type,
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'type',
                        'operator' => '=',
                        'value' => $type,
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
