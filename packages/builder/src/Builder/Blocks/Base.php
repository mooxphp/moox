<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

abstract class Base
{
    protected string $name;

    protected string $label;

    protected string $description;

    protected bool $nullable = false;

    protected $default = null;

    protected array $rules = [];

    protected array $extraAttributes = [];

    protected static array $useStatements = [];

    public function __construct(string $name, string $label, string $description)
    {
        $this->name = $name;
        $this->label = $label;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public static function getUseStatements(): array
    {
        return static::$useStatements;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function setDefault($default): self
    {
        $this->default = $default;

        return $this;
    }

    public function addRule(string $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function addExtraAttribute(string $key, $value): self
    {
        $this->extraAttributes[$key] = $value;

        return $this;
    }

    public function migration(): string
    {
        $type = $this->getMigrationType();
        $migration = "\$table->{$type}('{$this->name}')";

        if ($this->nullable) {
            $migration .= '->nullable()';
        }

        if ($this->default !== null) {
            $migration .= "->default({$this->getDefaultValue()})";
        }

        $migration .= $this->getExtraMigrationAttributes();

        return $migration.';';
    }

    abstract protected function getMigrationType(): string;

    protected function getDefaultValue(): string
    {
        return var_export($this->default, true);
    }

    protected function getExtraMigrationAttributes(): string
    {
        return '';
    }

    abstract public function formField(): string;

    abstract public function tableColumn(): string;

    public function modelAttribute(): string
    {
        return "'{$this->name}'";
    }

    public function modelCast(): string
    {
        return "'{$this->name}' => '{$this->getMigrationType()}'";
    }

    protected function applyCommonFormFieldAttributes(string $field): string
    {
        $field .= "->label('{$this->label}')";
        $field .= "->helperText('{$this->description}')";

        if ($this->nullable) {
            $field .= '->nullable()';
        }

        foreach ($this->rules as $rule) {
            $field .= "->rule('{$rule}')";
        }

        foreach ($this->extraAttributes as $key => $value) {
            $field .= "->{$key}({$value})";
        }

        return $field;
    }
}
