<?php

namespace Moox\Builder\Builder\Blocks;

class Toggle extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\Toggle;',
        'use Filament\Tables\Columns\IconColumn;',
        'use Filament\Tables\Filters\BooleanFilter;',
    ];

    protected ?string $onColor;

    protected ?string $offColor;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $default = false,
        ?string $onColor = null,
        ?string $offColor = null
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->setDefault($default);
        $this->onColor = $onColor;
        $this->offColor = $offColor;
    }

    protected function getMigrationType(): string
    {
        return 'boolean';
    }

    public function formField(): string
    {
        $field = "Toggle::make('{$this->name}')";

        if ($this->onColor) {
            $field .= "->onColor('{$this->onColor}')";
        }

        if ($this->offColor) {
            $field .= "->offColor('{$this->offColor}')";
        }

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
