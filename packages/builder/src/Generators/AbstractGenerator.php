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

    protected array $processedBlocks;

    protected array $generatedFiles = [];

    public function __construct(
        protected readonly BuildContext $context,
        array $blocks = []
    ) {
        $resolvedBlocks = $this->resolveBlocks($blocks);
        $this->processedBlocks = array_map(
            fn ($block) => $block->setContext($this->context),
            $resolvedBlocks
        );
    }

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
        foreach ($this->getBlocks() as $block) {
            $blockTraits = $block->getTraits($this->getGeneratorType());
            if (! empty($blockTraits)) {
                $traits = array_merge($traits, $blockTraits);
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

        foreach ($this->processedBlocks as $block) {
            $blockMethods = $block->getMethods($this->getGeneratorType());
            if (! empty($blockMethods)) {
                if (is_array($blockMethods)) {
                    foreach ($blockMethods as $method) {
                        if (is_array($method)) {
                            $methods = array_merge($methods, array_map('strval', $method));
                        } else {
                            $methods[] = (string) $method;
                        }
                    }
                } else {
                    $methods[] = (string) $blockMethods;
                }
            }
        }

        if (empty($methods)) {
            return '';
        }

        return implode("\n\n", array_unique(array_filter($methods)));
    }

    protected function getUseStatements(string $context, ?string $subContext = null): array
    {
        $statements = [];
        foreach ($this->getBlocks() as $block) {
            $blockStatements = $block->getUseStatements($context, $subContext);
            if (! empty($blockStatements)) {
                $statements = array_merge($statements, (array) $blockStatements);
            }
        }

        return array_unique(array_filter($statements));
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
            $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        // Clean up any remaining template variables
        $template = preg_replace('/\{\{\s*[a-zA-Z_]+\s*\}\}/', '', $template);

        // Clean up empty lines
        $template = preg_replace('/^\h*\v+/m', '', $template);
        $template = preg_replace('/\n\s*\n\s*\n/', "\n\n", $template);

        return $template;
    }

    protected function writeFile(string $path, string $content): void
    {
        $normalizedPath = $this->normalizePath($path);
        $this->ensureDirectoryExists($normalizedPath);
        file_put_contents($normalizedPath, $content);
        $this->generatedFiles[] = $normalizedPath;
    }

    public function formatGeneratedFiles(): void
    {
        if (empty($this->generatedFiles)) {
            return;
        }

        $files = implode(' ', array_map(
            fn ($file) => escapeshellarg($this->normalizePath($file)),
            $this->generatedFiles
        ));

        $command = "vendor/bin/pint {$files}";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function normalizePath(string $path): string
    {
        // Convert Windows backslashes to forward slashes
        $normalized = str_replace('\\', '/', $path);

        // Remove any double slashes
        return preg_replace('#/+#', '/', $normalized);
    }

    protected function getGeneratorConfig(): array
    {
        $generators = config('builder.generators', []);

        return $generators[$this->getGeneratorType()] ?? [];
    }

    protected function getBlocks(): array
    {
        return $this->processedBlocks;
    }

    protected function resolveBlocks(array $blocks): array
    {
        if (! empty($blocks)) {
            $firstBlock = reset($blocks);

            return $firstBlock->resolveBlockDependencies($blocks);
        }

        return [];
    }
}
