<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Blocks;

abstract class AbstractBlock
{
    protected string $name;

    protected string $label;

    protected string $description;

    protected bool $nullable = false;

    protected $default = null;

    protected array $rules = [];

    protected array $extraAttributes = [];

    protected array $useStatements = [
        'resource' => [
            'actions' => [],
            'columns' => [],
            'filters' => [],
            'forms' => [],
        ],
        'model' => [],
        'migration' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [],
            'view' => [],
        ],
    ];

    protected array $traits = [
        'resource' => [],
        'model' => [],
        'migration' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [],
            'view' => [],
        ],
    ];

    protected array $methods = [
        'resource' => [],
        'model' => [],
        'migration' => [],
        'pages' => [
            'create' => [],
            'edit' => [],
            'list' => [],
            'view' => [],
        ],
    ];

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

    public function getUseStatements(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->useStatements[$context][$subContext] ?? [];
        }

        return $this->useStatements[$context] ?? [];
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

    abstract public function getMigrationType(): string;

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

    abstract public function tableFilter(): string;

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

    public function getTraits(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->traits[$context][$subContext] ?? [];
        }

        return $this->traits[$context] ?? [];
    }

    public function getMethods(string $context, ?string $subContext = null): array
    {
        if ($subContext) {
            return $this->methods[$context][$subContext] ?? [];
        }

        return $this->methods[$context] ?? [];
    }

    protected function hasMultipleFields(): bool
    {
        return false;
    }

    protected function getAdditionalFields(): array
    {
        return [];
    }

    protected function getUniqueFields(): array
    {
        return [];
    }

    protected function getRequiredFields(): array
    {
        return [];
    }

    protected function getIndexedFields(): array
    {
        return [];
    }

    protected function getRelatedFields(): array
    {
        return [];
    }
}
