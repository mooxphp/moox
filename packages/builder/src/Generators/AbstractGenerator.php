<?php

declare(strict_types=1);

namespace Moox\Builder\Generators;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Traits\HandlesContentCleanup;
use Moox\Builder\Traits\HandlesIndentation;
use RuntimeException;

abstract class AbstractGenerator
{
    use HandlesContentCleanup;
    use HandlesIndentation;

    public function __construct(
        protected readonly BuildContext $context,
        protected readonly array $blocks = [],
        protected readonly array $features = []
    ) {}

    abstract public function generate(): void;

    abstract protected function getGeneratorType(): string;

    protected function getTemplate(): string
    {
        $generators = config('builder.generators', []);
        $generatorConfig = $generators[$this->getGeneratorType()] ?? null;

        if (! $generatorConfig || ! isset($generatorConfig['template'])) {
            throw new RuntimeException("No template configured for generator {$this->getGeneratorType()}");
        }

        return $generatorConfig['template'];
    }

    protected function formatUseStatements(): string
    {
        $statements = $this->getUseStatements($this->getGeneratorType());

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function formatTraits(): string
    {
        $traits = [];

        foreach ($this->features as $feature) {
            $featureTraits = $feature->getTraits($this->getGeneratorType());
            if (! empty($featureTraits)) {
                $traits = array_merge($traits, $featureTraits);
            }
        }

        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', array_unique($traits)).';';
    }

    protected function formatMethods(): string
    {
        $methods = [];

        foreach ($this->blocks as $block) {
            $blockMethods = $block->getMethods($this->getGeneratorType());
            if (! empty($blockMethods)) {
                $methods = array_merge($methods, $blockMethods);
            }
        }

        foreach ($this->features as $feature) {
            $featureMethods = $feature->getMethods($this->getGeneratorType());
            if (! empty($featureMethods)) {
                $methods = array_merge($methods, $featureMethods);
            }
        }

        if (empty($methods)) {
            return '';
        }

        return implode("\n\n", array_unique($methods));
    }

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

    protected function loadStub(string $name): string
    {
        $path = $this->getStubPath($name);

        if (! file_exists($path)) {
            throw new RuntimeException("Stub file not found: {$path}");
        }

        return file_get_contents($path);
    }

    protected function getStubPath(string $name): string
    {
        $templates = config('builder.templates', []);
        $template = $templates[$name] ?? null;

        if (! $template || ! isset($template['path'])) {
            throw new RuntimeException("Template not found: {$name}");
        }

        return $template['path'];
    }

    protected function replaceTemplateVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);

        if (config('builder.format_with_pint', true)) {
            $this->formatWithPint($path);
        }
    }

    protected function formatWithPint(string $path): void
    {
        $vendorPath = base_path('vendor/bin/pint');
        $command = sprintf('%s %s', $vendorPath, $path);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }

    protected function getGeneratorConfig(): array
    {
        $generators = config('builder.generators', []);

        return $generators[$this->getGeneratorType()] ?? [];
    }
}
