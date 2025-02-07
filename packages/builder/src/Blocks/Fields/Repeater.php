<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

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
                .($this->minItems ? sprintf('->minItems(%s)', $this->minItems) : '')
                .($this->maxItems ? sprintf('->maxItems(%s)', $this->maxItems) : ''),
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
            sprintf("\$table->json('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => '[]',
        ];

        $this->casts['model'] = [
            sprintf("'%s' => 'array'", $this->name),
        ];
    }
}
