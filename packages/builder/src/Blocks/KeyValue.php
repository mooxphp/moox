<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class KeyValue extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $keyLabel = true,
        protected bool $valueLabel = true,
        protected bool $reorderable = false,
        protected ?array $keyOptions = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\KeyValue;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\Filter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "KeyValue::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()')
                .(! $this->keyLabel ? '->disableKeyLabel()' : '')
                .(! $this->valueLabel ? '->disableValueLabel()' : '')
                .($this->reorderable ? '->reorderable()' : '')
                .($this->keyOptions ? '->keyOptions('.var_export($this->keyOptions, true).')' : ''),
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
