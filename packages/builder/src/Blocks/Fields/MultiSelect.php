<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class MultiSelect extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected array $options = [],
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\MultiSelect;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\MultiSelectFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "MultiSelect::make('{$this->name}')
                ->label('{$this->label}')
                ->options(".var_export($this->options, true).')'
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->listWithLineBreaks()",
        ];

        $this->filters['resource'] = [
            "MultiSelectFilter::make('{$this->name}')
                ->options(".var_export($this->options, true).')',
        ];

        $this->migrations['fields'] = [
            sprintf("\$table->json('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => 'fake()->randomElements('.var_export($this->options, true).', 2)',
        ];
    }
}
