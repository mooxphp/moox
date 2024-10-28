<?php

namespace Moox\Builder\Builder\Blocks;

class Number extends Base
{
    protected static array $useStatements = [
        'use Filament\Forms\Components\TextInput;',
        'use Filament\Tables\Columns\TextColumn;',
        'use Filament\Tables\Filters\NumberFilter;',
    ];

    protected bool $isFloat;

    protected ?int $min;

    protected ?int $max;

    protected ?float $step;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $isFloat = false,
        bool $nullable = false,
        ?int $min = null,
        ?int $max = null,
        ?float $step = null
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->isFloat = $isFloat;
        $this->min = $min;
        $this->max = $max;
        $this->step = $step;
    }

    protected function getMigrationType(): string
    {
        return $this->isFloat ? 'float' : 'integer';
    }

    public function formField(): string
    {
        $field = "TextInput::make('{$this->name}')->numeric()";
        if ($this->min !== null) {
            $field .= "->minValue({$this->min})";
        }
        if ($this->max !== null) {
            $field .= "->maxValue({$this->max})";
        }
        if ($this->step !== null) {
            $field .= "->step({$this->step})";
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->numeric(".($this->isFloat ? '2' : '0').')';
    }

    public function tableFilter(): string
    {
        return "NumberFilter::make('{$this->name}')";
    }
}
