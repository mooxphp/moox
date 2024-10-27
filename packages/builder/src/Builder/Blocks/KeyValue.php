<?php

namespace Moox\Builder\Blocks;

class KeyValue extends Base
{
    protected bool $keyLabel;

    protected bool $valueLabel;

    public function __construct(
        string $name,
        string $label,
        string $description,
        bool $nullable = false,
        bool $keyLabel = true,
        bool $valueLabel = true
    ) {
        parent::__construct($name, $label, $description);
        $this->setNullable($nullable);
        $this->keyLabel = $keyLabel;
        $this->valueLabel = $valueLabel;
    }

    protected function getMigrationType(): string
    {
        return 'json';
    }

    public function formField(): string
    {
        $field = "KeyValue::make('{$this->name}')";
        if (! $this->keyLabel) {
            $field .= '->disableKeyLabel()';
        }
        if (! $this->valueLabel) {
            $field .= '->disableValueLabel()';
        }

        return $this->applyCommonFormFieldAttributes($field);
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->json()";
    }

    public function tableFilter(): string
    {
        return "Filter::make('has_{$this->name}')->query(fn (\$query) => \$query->whereNotNull('{$this->name}'))";
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => 'array'";
    }
}
