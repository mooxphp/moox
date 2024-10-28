<?php

namespace Moox\Builder\Builder\Blocks;

class Bool extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Toggle;',
        'use Filament\Tables\Columns\IconColumn;',
        'use Filament\Tables\Filters\BooleanFilter;',
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
