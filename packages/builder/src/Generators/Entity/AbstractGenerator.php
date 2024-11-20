<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;
use Moox\Builder\Traits\HandlesContentCleanup;
use Moox\Builder\Traits\HandlesIndentation;
use RuntimeException;

abstract class AbstractGenerator
{
    use HandlesContentCleanup;
    use HandlesIndentation;

    protected array $processedBlocks;

    protected array $generatedFiles = [];

    protected string $entityName;

    protected string $pluralName;

    public function __construct(
        protected readonly BuildContext $context,
        protected readonly FileManager $fileManager,
        array $blocks = []
    ) {
        $this->entityName = $this->context->getEntityName();
        $this->pluralName = $this->context->getPluralName();
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
        $templates = $this->context->getTemplate($this->getGeneratorType());
        $templatePath = $templates['path'] ?? null;

        if (! $templatePath || ! file_exists($templatePath)) {
            throw new RuntimeException(
                "Template file for {$this->getGeneratorType()} not found at {$templatePath}"
            );
        }

        return file_get_contents($templatePath);
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

    protected function loadStub(string $path): string
    {
        if (! file_exists($path)) {
            throw new RuntimeException("Template not found: {$path}");
        }

        return file_get_contents($path);
    }

    protected function replaceTemplateVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        $template = preg_replace('/\{\{\s*[a-zA-Z_]+\s*\}\}/', '', $template);
        $template = preg_replace('/^\h*\v+/m', '', $template);
        $template = preg_replace('/\n\s*\n\s*\n/', "\n\n", $template);

        return $template;
    }

    protected function writeFile(string $path, string $content): void
    {
        $path = str_replace('\\', '/', $path);
        $this->generatedFiles[$this->getGeneratorType()] = [
            'path' => $path,
            'content' => $content,
        ];
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

    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    protected function formatGeneratedFiles(): void
    {
        if (! empty($this->generatedFiles)) {
            $this->fileManager->formatFiles($this->generatedFiles);
        }
    }
}
