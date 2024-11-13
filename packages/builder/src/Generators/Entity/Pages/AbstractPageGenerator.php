<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

use Moox\Builder\Contexts\BuildContext;

abstract class AbstractPageGenerator
{
    protected BuildContext $context;

    protected array $blocks;

    protected string $resourceName;

    public function __construct(BuildContext $context, array $blocks)
    {
        $this->context = $context;
        $this->blocks = $blocks;
        $this->resourceName = $this->context->getEntityName().'Resource';
    }

    abstract protected function getPageType(): string;

    public function generate(): void
    {
        $template = $this->loadStub();
        $className = $this->getClassName();

        $variables = [
            'namespace' => $this->getNamespace(),
            'resource_namespace' => $this->context->getNamespace('resource'),
            'resource_class' => $this->resourceName,
            'class_name' => $className,
            'model' => $this->context->getEntityName(),
            'model_plural' => $this->context->getPluralModelName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'methods' => $this->formatMethods(),
            'resource' => $this->resourceName,
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($content, $className);
    }

    protected function loadStub(): string
    {
        return file_get_contents($this->context->getPageTemplate('resource', $this->getPageType()));
    }

    protected function getClassName(): string
    {
        return $this->getPageType() === 'List'
            ? $this->getPageType().$this->context->getPluralModelName()
            : $this->getPageType().$this->context->getEntityName();
    }

    protected function getNamespace(): string
    {
        return $this->context->getNamespace('resource').'\\'.$this->resourceName.'\\Pages';
    }

    protected function formatUseStatements(): string
    {
        $statements = [
            $this->context->getNamespace('resource').'\\'.$this->resourceName,
        ];

        foreach ($this->blocks as $block) {
            if (! empty($block->useStatements['pages'][strtolower($this->getPageType())])) {
                $statements = array_merge(
                    $statements,
                    $block->useStatements['pages'][strtolower($this->getPageType())]
                );
            }
        }

        return implode("\n", array_unique($statements));
    }

    protected function formatTraits(): string
    {
        $traits = [];
        foreach ($this->blocks as $block) {
            if (! empty($block->traits['pages'][strtolower($this->getPageType())])) {
                $traits = array_merge($traits, $block->traits['pages'][strtolower($this->getPageType())]);
            }
        }

        return empty($traits) ? '' : 'use '.implode(', ', array_unique($traits)).';';
    }

    protected function formatMethods(): string
    {
        $methods = [];
        foreach ($this->blocks as $block) {
            if (! empty($block->methods['pages'][strtolower($this->getPageType())])) {
                $methods = array_merge($methods, (array) $block->methods['pages'][strtolower($this->getPageType())]);
            }
        }

        return empty($methods) ? '' : implode("\n\n", array_unique($methods));
    }

    protected function replaceTemplateVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        return $template;
    }

    protected function writeFile(string $content, string $className): void
    {
        $path = str_replace('\\', '/', $this->context->getPath('resource').'/'.$this->resourceName.'/Pages');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = $path.'/'.$className.'.php';
        file_put_contents($filePath, $content);
    }
}
