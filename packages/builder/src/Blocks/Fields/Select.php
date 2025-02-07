<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class Select extends AbstractBlock
{
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
                'forms' => [sprintf('use Filament\Forms\Components\%s;', $componentClass)],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => [sprintf('use Filament\Tables\Filters\%s;', $filterClass)],
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
            sprintf("TextColumn::make('%s')", $this->name)
                .($this->multiple ? '->listWithLineBreaks()' : ''),
        ];

        $this->filters['resource'] = [
            "{$filterClass}::make('{$this->name}')
                ->options(".var_export($this->options, true).')',
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->multiple ? 'json' : 'string').sprintf("('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => $this->multiple
                ? 'fake()->randomElements('.var_export(array_keys($this->options), true).', 2)'
                : 'fake()->randomElement('.var_export(array_keys($this->options), true).')',
        ];
    }
}
