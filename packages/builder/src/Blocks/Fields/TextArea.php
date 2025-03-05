<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class TextArea extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $searchable = false,
        protected bool $sortable = false,
        protected ?int $maxLength = null,
        protected ?int $rows = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\Textarea;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->addSection('form')
            ->withFields(["Textarea::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()')
                .($this->maxLength ? sprintf('->maxLength(%s)', $this->maxLength) : '')
                .($this->rows ? sprintf('->rows(%s)', $this->rows) : ''),
            ]);

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->limit(50)"
                .($this->searchable ? '->searchable()' : '')
                .($this->sortable ? '->sortable()' : ''),
        ];

        $this->migrations['fields'] = [
            sprintf("\$table->text('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => 'fake()->paragraphs(2, true)',
        ];
    }
}
