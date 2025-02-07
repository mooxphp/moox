<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class FileUpload extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $multiple = false,
        protected string $directory = 'uploads',
        protected array $acceptedFileTypes = [],
        protected int $maxSize = 1024,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\FileUpload;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->formFields['resource'] = [
            "FileUpload::make('{$this->name}')
                ->label('{$this->label}')
                ->directory('{$this->directory}')"
                .($this->multiple ? '->multiple()' : '')
                .($this->acceptedFileTypes ? "->acceptedFileTypes(['".implode("','", $this->acceptedFileTypes)."'])" : '')
                .sprintf('->maxSize(%d)', $this->maxSize)
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            sprintf("TextColumn::make('%s')", $this->name),
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->multiple ? 'json' : 'string').sprintf("('%s')", $this->name)
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            $this->name => $this->multiple
                ? '[]'
                : "''",
        ];
    }
}
