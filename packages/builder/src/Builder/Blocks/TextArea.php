<?php

namespace Moox\Builder\Builder\Blocks;

class TextArea extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Textarea;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\TextFilter;',
    ];

    protected bool $searchable;

    protected bool $sortable;

    protected ?int $rows;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $searchable = false,
        bool $sortable = false,
        ?int $rows = null
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->searchable = $searchable;
        $this->sortable = $sortable;
        $this->rows = $rows;
    }

    protected function getMigrationType(): string
    {
        return 'text';
    }

    public function formField(): string
    {
        $field = "Textarea::make('{$this->name}')";
        if ($this->rows !== null) {
            $field .= "->rows({$this->rows})";
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        $column = "TextColumn::make('{$this->name}')->limit(50)";
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
