<?php

declare(strict_types=1);

namespace Moox\Builder\Blocks;

class Text extends AbstractBlock
{
    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        protected int $length = 255,
        protected bool $unique = false,
        protected bool $searchable = false,
        protected bool $sortable = false,
    ) {
        parent::__construct($name, $label, $description, $nullable);

        $this->useStatements['resource'] = [
            'forms' => ['use Filament\Forms\Components\TextInput;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
        ];

        $this->formFields['resource'] = [
            "TextInput::make('{$this->name}')
                ->label('{$this->label}')
                ->maxLength({$this->length})"
                .($this->nullable ? '->nullable()' : '->required()'),
        ];

        $this->tableColumns['resource'] = [
            "TextColumn::make('{$this->name}')"
                .($this->sortable ? '->sortable()' : '')
                .($this->searchable ? '->searchable()' : ''),
        ];

        $this->migrations['fields'] = [
            "\$table->string('{$this->name}', {$this->length})"
                .($this->nullable ? '->nullable()' : ''),
        ];

        if ($this->unique) {
            $this->migrations['indexes'][] = "\$table->unique('{$this->name}')";
        }

        $this->factories['model']['definitions'] = [
            "{$this->name}" => "fake()->text({$this->length})",
        ];
    }
}
