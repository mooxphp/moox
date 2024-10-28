<?php

namespace Moox\Builder\Builder\Blocks;

class DateTime extends Base
{
    protected bool $sortable;

    protected bool $withSeconds;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $sortable = false,
        bool $withSeconds = false
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->sortable = $sortable;
        $this->withSeconds = $withSeconds;
    }

    protected function getMigrationType(): string
    {
        return 'dateTime';
    }

    public function formField(): string
    {
        $field = "DateTimePicker::make('{$this->name}')";
        if ($this->withSeconds) {
            $field .= '->withSeconds()';
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        $column = "TextColumn::make('{$this->name}')->dateTime()";
        if ($this->sortable) {
            $column .= '->sortable()';
        }

        return $column;
    }

    public function tableFilter(): string
    {
        return "DateTimeFilter::make('{$this->name}')";
    }
}
