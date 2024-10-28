<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class PluginGenerator
{
    protected string $namespace;

    protected string $className;

    protected string $id;

    /** @var array<Feature> */
    protected array $features = [];

    /** @var array<string> */
    protected array $resources = [];

    protected array $useStatements = [];

    protected array $bootMethods = [];

    protected array $additionalMethods = [];

    public function __construct(
        string $namespace,
        string $className,
        string $id
    ) {
        $this->namespace = $namespace;
        $this->className = $className;
        $this->id = $id;
    }

    public function addFeature(Feature $feature): self
    {
        $this->features[] = $feature;

        return $this;
    }

    public function addResource(string $resource): self
    {
        $this->resources[] = $resource;

        return $this;
    }

    public function addUseStatement(string $statement): self
    {
        $this->useStatements[] = $statement;

        return $this;
    }

    public function addBootMethod(string $method): self
    {
        $this->bootMethods[] = $method;

        return $this;
    }

    public function addMethod(string $method): self
    {
        $this->additionalMethods[] = $method;

        return $this;
    }

    protected function getUseStatements(): array
    {
        $statements = $this->useStatements;
        foreach ($this->features as $feature) {
            $statements = array_merge($statements, $feature->getPluginUseStatements());
        }

        return array_unique($statements);
    }

    protected function getBootMethods(): array
    {
        $methods = $this->bootMethods;
        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getPluginBootMethods());
        }

        return array_unique($methods);
    }

    protected function getMethods(): array
    {
        $methods = $this->additionalMethods;
        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getPluginMethods());
        }

        return array_unique($methods);
    }

    public function generate(): string
    {
        $template = file_get_contents(__DIR__.'/../Templates/plugin.php.stub');

        $replacements = [
            '{{ namespace }}' => $this->namespace,
            '{{ class_name }}' => $this->className,
            '{{ id }}' => $this->id,
            '{{ use_statements }}' => implode("\n", $this->getUseStatements()),
            '{{ resources }}' => implode(",\n            ", array_map(
                fn ($resource) => "$resource::class",
                $this->resources
            )),
            '{{ boot_methods }}' => implode("\n        ", $this->getBootMethods()),
            '{{ methods }}' => implode("\n\n    ", $this->getMethods()),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
}
