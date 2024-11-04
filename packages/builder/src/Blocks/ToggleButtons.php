<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class ToggleButtons extends AbstractBlock
{
    protected bool $isFeature = false;

    protected bool $isSingleFeature = false;

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
                'forms' => ['use Filament\Forms\Components\ToggleButtons;'],
                'columns' => ['use Filament\Tables\Columns\TextColumn;'],
                'filters' => ['use Filament\Tables\Filters\SelectFilter;'],
            ],
        ];

        $this->formFields['resource'] = [
            "ToggleButtons::make('{$this->name}')
                ->label('{$this->label}')
                ->options(".var_export($this->options, true).')'
                .($this->nullable ? '' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')",
        ];

        $this->filters['resource'] = [
            "SelectFilter::make('{$this->name}')
                ->options(".var_export($this->options, true).')',
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->name}')"
                .($this->nullable ? '->nullable()' : ''),
        ];

        $this->factories['model']['definitions'] = [
            "{$this->name}" => 'fake()->randomElement('.var_export(array_keys($this->options), true).')',
        ];
    }
}
