<?php

namespace Moox\Builder\Blocks;

class MultiSelect extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\MultiSelect;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\MultiSelectFilter;',
    ];

    protected array $options;

    public function __construct(
        string $name,
        string $label,
        string $description,
        array $options,
        bool $nullable = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->options = $options;
    }

    protected function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "MultiSelect::make('{$this->name}')";
        $field .= '->options('.var_export($this->options, true).')';

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->listWithLineBreaks()";
    }

    public function tableFilter(): string
    {
        return "MultiSelectFilter::make('{$this->name}')->options(".var_export($this->options, true).')';
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'array'";
    }
}
