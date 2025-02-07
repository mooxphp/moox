<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class RichEditor extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $searchable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\RichEditor;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->addSection('form')
            ->withFields([
                "RichEditor::make('{$this->name}')
                    ->label('{$this->label}')"
                    .($this->nullable ? '' : '->required()'),
            ]);

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->html()
                ->limit(50)"
                .($this->searchable ? '->searchable()' : ''),
        ];

        $this->migrations['fields'] = [
            "\$table->text('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->paragraphs(3, true)',
        ];

        $this->casts['model'] = [
            "'{$this->name}' => 'string'",
        ];
    }
}
