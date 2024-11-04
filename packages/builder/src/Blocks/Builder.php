<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Builder extends AbstractBlock
{
    protected bool $isFeature = false;

    protected bool $isSingleFeature = false;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected array $blocks = [],
        protected bool $collapsible = false,
        protected bool $cloneable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\Builder;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\Filter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "Builder::make('{$this->name}')
                ->label('{$this->label}')
                ->blocks([".implode(', ', array_map(fn ($block) => $block->formField(), $this->blocks)).'])'
                .($this->nullable ? '' : '->required()')
                .($this->collapsible ? '->collapsible()' : '')
                .($this->cloneable ? '->cloneable()' : ''),
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
