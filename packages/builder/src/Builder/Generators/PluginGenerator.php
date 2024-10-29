<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class PluginGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub('plugin');

        $variables = [
            'namespace' => $this->entityNamespace,
            'class_name' => $this->entityName,
            'id' => strtolower($this->entityName),
            'use_statements' => $this->formatUseStatements(),
            'resources' => $this->getResources(),
            'boot_methods' => $this->getBootMethods(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getPluginPath(), $content);
    }

    protected function formatUseStatements(): string
    {
        $statements = $this->getUseStatements('plugin');

        return implode("\n", array_unique($statements));
    }

    protected function getResources(): string
    {
        // Implement logic to get resources
        return '';
    }

    protected function getBootMethods(): string
    {
        // Implement logic to get boot methods
        return '';
    }

    protected function formatMethods(): string
    {
        $methods = $this->getMethods('plugin');

        return implode("\n\n    ", array_filter($methods));
    }

    protected function getPluginPath(): string
    {
        return $this->entityPath.'/Plugins/'.$this->entityName.'Plugin.php';
    }
}
