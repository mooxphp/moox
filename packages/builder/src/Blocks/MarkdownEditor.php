<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class MarkdownEditor extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $searchable = false,
        // push into section, so that preset can use sections
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\MarkdownEditor;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\TextFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "MarkdownEditor::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->markdown()"
                .($this->searchable ? '->searchable()' : ''),
        ];

        $this->filters['resource'] = [
            "TextFilter::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            "\$table->text('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->paragraphs(3, true)',
        ];
    }
}
