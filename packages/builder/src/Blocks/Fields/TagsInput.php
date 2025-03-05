<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class TagsInput extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected ?array $suggestions = null,
        protected ?string $separator = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\TagsInput;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\MultiSelectFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "TagsInput::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()')
                .($this->suggestions ? '->suggestions('.var_export($this->suggestions, true).')' : '')
                .($this->separator ? sprintf("->separator('%s')", $this->separator) : ''),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->listWithLineBreaks()",
        ];

        $this->filters['resource'] = [
            sprintf("MultiSelectFilter::make('%s')", $this->name)
                .($this->suggestions ? '->options('.var_export($this->suggestions, true).')' : ''),
        ];

        $this->migrations['fields'] = [
            sprintf("\$table->json('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => $this->suggestions
                ? 'fake()->randomElements('.var_export($this->suggestions, true).', 2)'
                : '[fake()->word(), fake()->word()]',
        ];

        $this->casts['model'] = [
            sprintf("'%s' => 'array'", $this->name),
        ];
    }
}
