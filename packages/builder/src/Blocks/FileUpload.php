<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

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
                ."->maxSize({$this->maxSize})"
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            '$table->'.($this->multiple ? 'json' : 'string')."('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => $this->multiple
                ? '[]'
                : "''",
        ];
    }
}
