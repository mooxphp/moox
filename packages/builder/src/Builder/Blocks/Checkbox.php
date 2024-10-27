<?php

namespace Moox\Builder\Blocks;

class Checkbox extends Base
{
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
        $field = "Checkbox::make('{$this->name}')";

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
