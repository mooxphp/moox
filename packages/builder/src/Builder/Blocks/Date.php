<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Date extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\DatePicker;'],
            'columns' => ['use Filament\Tables\Columns\TextColumn;'],
            'filters' => ['use Filament\Tables\Filters\DateFilter;'],
        ],
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
