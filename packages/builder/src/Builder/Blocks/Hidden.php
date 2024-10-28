<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Hidden extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\Hidden;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
        ],
    ];

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
    }

    protected function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        return "Hidden::make('{$this->name}')";
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->hidden()";
    }

    public function tableFilter(): string
    {
        return '';
    }
}
