<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class ModelGenerator
{
    protected string $namespace;

    protected string $className;

    protected string $table;

    /** @var array<Feature> */
    protected array $features = [];

    protected array $fillable = [];

    protected array $casts = [];

    protected array $additionalMethods = [];

    public function __construct(string $namespace, string $className, string $table)
    {
        $this->namespace = $namespace;
        $this->className = $className;
        $this->table = $table;
    }

    public function addFeature(Feature $feature): self
    {
        $this->features[] = $feature;

        return $this;
    }

    public function addFillable(string $field): self
    {
        $this->fillable[] = $field;

        return $this;
    }

    public function addCast(string $field, string $type): self
    {
        $this->casts[$field] = $type;

        return $this;
    }

    public function addMethod(string $method): self
    {
        $this->additionalMethods[] = $method;

        return $this;
    }

    protected function getUseStatements(): array
    {
        $statements = ['use Illuminate\Database\Eloquent\Model;'];
        foreach ($this->features as $feature) {
            $statements = array_merge($statements, $feature->getModelUseStatements());
        }

        return array_unique($statements);
    }

    protected function getTraits(): array
    {
        $traits = [];
        foreach ($this->features as $feature) {
            $traits = array_merge($traits, $feature->getModelTraits());
        }

        return array_unique($traits);
    }

    protected function getMethods(): array
    {
        $methods = [];
        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getModelMethods());
        }

        return array_merge($methods, $this->additionalMethods);
    }

    public function generate(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/model.php.stub');

        $replacements = [
            '{{ namespace }}' => $this->namespace,
            '{{ class_name }}' => $this->className,
            '{{ table }}' => $this->table,
            '{{ use_statements }}' => implode("\n", $this->getUseStatements()),
            '{{ traits }}' => empty($this->getTraits()) ? '' : 'use '.implode(', ', $this->getTraits()).';',
            '{{ fillable }}' => implode(",\n        ", array_map(fn ($field) => "'$field'", $this->fillable)),
            '{{ casts }}' => implode(",\n        ", array_map(
                fn ($field, $type) => "'$field' => '$type'",
                array_keys($this->casts),
                array_values($this->casts)
            )),
            '{{ methods }}' => implode("\n\n    ", $this->getMethods()),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
