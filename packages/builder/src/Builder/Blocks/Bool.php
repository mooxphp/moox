<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

class Bool extends AbstractBlock
{
    protected array $useStatements = [
        'resource' => [
            'forms' => ['use Filament\Forms\Components\Toggle;'],
            'columns' => ['use Filament\Tables\Columns\IconColumn;'],
            'filters' => ['use Filament\Tables\Filters\BooleanFilter;'],
        ],
    ];

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $default = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->setDefault($default);
    }

    protected function getMigrationType(): string
    {
        return 'boolean';
    }

    public function formField(): string
    {
        $field = "Toggle::make('{$this->name}')";

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "IconColumn::make('{$this->name}')->boolean()";
    }

    public function tableFilter(): string
    {
        return "BooleanFilter::make('{$this->name}')";
    }
}
