<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Text extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\TextInput;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\TextFilter;'],
        ],
    ];

    protected int $length;

    protected bool $unique;

    protected bool $searchable;

    protected bool $sortable;

    protected bool $primary;

    protected bool $index;

    public function __construct(
        string $name,
        string $label,
        string $description,
        int $length = 255,
        bool $nullable = false,
        bool $unique = false,
        bool $searchable = false,
        bool $sortable = false,
        bool $primary = false,
        bool $index = false
    ) {
        parent::__construct($name, $label, $description);
        $this->length = $length;
        $this->setNullable($nullable);
        $this->unique = $unique;
        $this->searchable = $searchable;
        $this->sortable = $sortable;
        $this->primary = $primary;
        $this->index = $index;
    }

    public function getMigrationType(): string
    {
        return 'string';
    }

    public function formField(): string
    {
        $field = "TextInput::make('{$this->name}')";
        $field .= "->maxLength({$this->length})";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        $column = "TextColumn::make('{$this->name}')";
        if ($this->sortable) {
            $column .= '->sortable()';
        }
        if ($this->searchable) {
            $column .= '->searchable()';
        }

        return $column;
    }

    public function tableFilter(): string
    {
        return "TextFilter::make('{$this->name}')";
    }
}
