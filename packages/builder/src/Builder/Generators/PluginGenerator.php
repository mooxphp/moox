<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesContentCleanup;

class PluginGenerator extends AbstractGenerator
{
    use HandlesContentCleanup;

    public function generate(): void
    {
        $template = $this->loadStub('plugin');

        $variables = [
            'namespace' => $this->context->getPluginNamespace(),
            'class_name' => $this->context->getEntityName(),
            'id' => strtolower($this->context->getEntityName()),
            'use_statements' => $this->formatUseStatements(),
            'resources' => $this->getResources(),
            'boot_methods' => $this->getBootMethods(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->context->getPluginPath(), $content);
    }

    protected function getResources(): string
    {
        return $this->context->getEntityName().'Resource::class';
    }

    protected function getBootMethods(): string
    {
        return '//';
    }

    protected function getGeneratorType(): string
    {
        return 'plugin';
    }

    protected function formatUseStatements(): string
    {
        $statements = [
            'use '.$this->context->getResourceNamespace().'\\'.$this->context->getEntityName().'Resource;',
        ];

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }
}
