<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Filament;

use Moox\Builder\Blocks\AbstractBlock;

class Hidden extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\Hidden;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            ],
        ];

        $this->formFields['resource'] = [
            "Hidden::make('{$this->name}')",
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')
                ->hidden()",
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->word()',
        ];
    }
}
