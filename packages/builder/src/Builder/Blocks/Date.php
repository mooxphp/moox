<?php

namespace Moox\Builder\Builder\Blocks;

class Date extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\DatePicker;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\DateFilter;',
    ];

    protected bool $sortable;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $sortable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->sortable = $sortable;
    }

    protected function getMigrationType(): string
    {
        return 'date';
    }

    public function formField(): string
    {
        $field = "DatePicker::make('{$this->name}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        $column = "TextColumn::make('{$this->name}')->date()";
        if ($this->sortable) {
            $column .= '->sortable()';
        }

        return $column;
    }

    public function tableFilter(): string
    {
        return "DateFilter::make('{$this->name}')";
    }
}
