<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks\Fields;

use Moox\Builder\Blocks\AbstractBlock;

class Toggle extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected bool $default = false,
        protected ?string $onColor = null,
        protected ?string $offColor = null,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements = [
            'resource' => [
                'forms' => ['use Filament\Forms\Components\Toggle;'],
                'columns' => ['use Filament\Tables\Columns\IconColumn;'],
                'filters' => ['use Filament\Tables\Filters\BooleanFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "Toggle::make('{$this->name}')
                ->label('{$this->label}')"
                .($this->nullable ? '' : '->required()')
                .($this->default ? '->default(true)' : '')
                .($this->onColor ? "->onColor('{$this->onColor}')" : '')
                .($this->offColor ? "->offColor('{$this->offColor}')" : ''),
        ];

        $this->tableColumns['resource'] = [
            "IconColumn::make('{$this->name}')
                ->boolean()",
        ];

        $this->filters['resource'] = [
            "BooleanFilter::make('{$this->name}')",
        ];

        $this->migrations['fields'] = [
            "\$table->boolean('{$this->name}')"
                .($this->nullable ? '->nullable()' : '')
                .($this->default ? '->default(true)' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => $this->default ? 'true' : 'fake()->boolean()',
        ];
    }
}
