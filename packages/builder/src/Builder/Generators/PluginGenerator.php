<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class PluginGenerator extends AbstractGenerator
{
    public function __construct(
        string $entityName,
        string $entityNamespace,
        string $entityPath,
        array $blocks,
        array $features
    ) {
        parent::__construct($entityName, $entityNamespace, $entityPath, $blocks, $features);
    }

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
        $statements = array_merge(
            $this->getUseStatements('plugin'),
            [
                'use '.$this->entityNamespace.'\\Filament\\Resources\\'.$this->entityName.'Resource;',
            ]
        );

        return implode("\n", array_unique($statements));
    }

    protected function getResources(): string
    {
        return $this->entityName.'Resource::class';
    }

    protected function getBootMethods(): string
    {
        return '//';
    }

    protected function formatMethods(): string
    {
        $methods = $this->getMethods('plugin');

        return implode("\n\n    ", array_filter($methods));
    }

    protected function getPluginPath(): string
    {
        return $this->entityPath.'/Filament/Plugins/'.$this->entityName.'Plugin.php';
    }
}
