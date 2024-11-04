<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Select extends AbstractBlock
{
    protected bool $isFeature = false;

    protected bool $isSingleFeature = false;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected array $options = [],
        protected bool $multiple = false,
        protected bool $searchable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $componentClass = $this->multiple ? 'MultiSelect' : 'Select';
        $filterClass = $this->multiple ? 'MultiSelectFilter' : 'SelectFilter';

        $this->useStatements = [
            'resource' => [
                'forms' => ["use Filament\Forms\Components\\{$componentClass};"],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ["use Filament\Tables\Filters\\{$filterClass};"],
            ],
        ];

        $this->formFields['resource'] = [
            "{$componentClass}::make('{$this->name}')
                ->label('{$this->label}')
                ->options(".var_export($this->options, true).')'
                .($this->nullable ? '' : '->required()')
                .($this->searchable ? '->searchable()' : ''),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')"
                .($this->multiple ? '->listWithLineBreaks()' : ''),
        ];

        $this->filters['resource'] = [
            "{$filterClass}::make('{$this->name}')
                ->options(".var_export($this->options, true).')',
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->multiple ? 'json' : 'string')."('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => $this->multiple
                ? 'fake()->randomElements('.var_export(array_keys($this->options), true).', 2)'
                : 'fake()->randomElement('.var_export(array_keys($this->options), true).')',
        ];
    }
}
