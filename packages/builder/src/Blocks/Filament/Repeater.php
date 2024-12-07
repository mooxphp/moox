<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class Repeater extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected array $schema = [],
        protected bool $collapsible = false,
        protected bool $cloneable = false,
        protected ?int $minItems = null,
        protected ?int $maxItems = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\Repeater;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\Filter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "Repeater::make('{$this->name}')
                ->label('{$this->label}')
                ->schema([".implode(', ', array_map(fn ($item) => $item->formField(), $this->schema)).'])'
                .($this->nullable ? '' : '->required()')
                .($this->collapsible ? '->collapsible()' : '')
                .($this->cloneable ? '->cloneable()' : '')
                .($this->minItems ? "->minItems({$this->minItems})" : '')
                .($this->maxItems ? "->maxItems({$this->maxItems})" : ''),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->json()",
        ];

        $this->filters['resource'] = [
            "Filter::make('has_{$this->name}')
                ->query(fn (\$query) => \$query->whereNotNull('{$this->name}'))",
        ];

        $this->migrations['fields'] = [
            "\$table->json('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => '[]',
        ];

        $this->casts['model'] = [
            "'{$this->name}' => 'array'",
        ];
    }
}
