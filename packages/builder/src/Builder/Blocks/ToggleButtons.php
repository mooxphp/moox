<?php

namespace Moox\Builder\Builder\Blocks;

class ToggleButtons extends Base
{
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
        return 'string';
    }

    public function formField(): string
    {
        $field = "ToggleButtons::make('{$this->name}')";
        $field .= '->options('.var_export($this->options, true).')';

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')";
    }

    public function tableFilter(): string
    {
        return "SelectFilter::make('{$this->name}')->options(".var_export($this->options, true).')';
    }
}
