<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\GeneratorTrait;

abstract class AbstractGenerator
{
    use GeneratorTrait;

    protected string $entityName;

    protected string $entityNamespace;

    protected string $entityPath;

    protected array $blocks;

    protected array $features;

    public function __construct(
        string $entityName,
        string $entityNamespace = '',
        string $entityPath = '',
        array $blocks = [],
        array $features = []
    ) {
        $this->entityName = $entityName;
        $this->entityNamespace = $entityNamespace;
        $this->entityPath = $entityPath;
        $this->blocks = $blocks;
        $this->features = $features;
    }

    abstract public function generate(): void;

    protected function getUseStatements(string $context, ?string $subContext = null): array
    {
        $statements = [];

        foreach ($this->blocks as $block) {
            $statements = array_merge($statements, $block->getUseStatements($context, $subContext));
        }

        foreach ($this->features as $feature) {
            $statements = array_merge($statements, $feature->getUseStatements($context, $subContext));
        }

        return array_unique($statements);
    }

    protected function getTraits(string $context, ?string $subContext = null): array
    {
        $traits = [];

        foreach ($this->blocks as $block) {
            $traits = array_merge($traits, $block->getTraits($context, $subContext));
        }

        foreach ($this->features as $feature) {
            $traits = array_merge($traits, $feature->getTraits($context, $subContext));
        }

        return array_unique($traits);
    }

    protected function getMethods(string $context, ?string $subContext = null): array
    {
        $methods = [];

        foreach ($this->blocks as $block) {
            $methods = array_merge($methods, $block->getMethods($context, $subContext));
        }

        foreach ($this->features as $feature) {
            $methods = array_merge($methods, $feature->getMethods($context, $subContext));
        }

        return array_unique($methods);
    }
}
