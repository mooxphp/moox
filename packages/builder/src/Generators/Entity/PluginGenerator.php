<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;
use Override;

class PluginGenerator extends AbstractGenerator
{
    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
    }

    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'namespace' => $this->context->getNamespace('plugin'),
            'class_name' => $this->context->getEntityName(),
            'id' => strtolower($this->context->getEntityName()),
            'use_statements' => $this->formatUseStatements(),
            'resources' => $this->getResources(),
            'boot_methods' => $this->getBootMethods(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->context->getPath('plugin').'/'.
            $this->context->getEntityName().'Plugin.php';
        $this->writeFile($path, $content);
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

    #[Override]
    protected function formatUseStatements(): string
    {
        $statements = [
            'use '.$this->context->getNamespace('resource').'\\'.$this->context->getEntityName().'Resource;',
        ];

        return implode("\n", array_map(fn ($statement): string => rtrim($statement, ';').';', array_unique($statements)));
    }
}
