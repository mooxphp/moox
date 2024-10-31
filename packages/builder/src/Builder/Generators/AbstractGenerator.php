<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Contexts\BuildContext;
use Moox\Builder\Builder\Traits\GeneratorTrait;
use Moox\Builder\Builder\Traits\HandlesIndentation;

abstract class AbstractGenerator
{
    use GeneratorTrait;
    use HandlesIndentation;

    public function __construct(
        protected readonly BuildContext $context,
        protected readonly array $blocks = [],
        protected readonly array $features = []
    ) {}

    abstract public function generate(): void;

    abstract protected function getGeneratorType(): string;

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

    protected function formatUseStatements(): string
    {
        $statements = $this->getUseStatements($this->getGeneratorType());

        return implode("\n", array_unique($statements));
    }

    protected function formatTraits(?string $context = null, ?string $subContext = null): string
    {
        $traits = $this->getTraits($context ?? $this->getGeneratorType(), $subContext);
        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', $traits).';';
    }

    protected function formatMethods(?string $context = null, ?string $subContext = null): string
    {
        $methods = $this->getMethods($context ?? $this->getGeneratorType(), $subContext);

        return implode("\n\n    ", array_filter($methods));
    }
}
