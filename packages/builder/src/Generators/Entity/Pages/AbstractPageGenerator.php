<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity\Pages;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\AbstractGenerator;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

abstract class AbstractPageGenerator extends AbstractGenerator
{
    protected string $resourceName;

    public function __construct(
        BuildContext $context,
        FileManager $fileManager,
        array $blocks = []
    ) {
        parent::__construct($context, $fileManager, $blocks);
        $this->initializeProperties();
    }

    protected function initializeProperties(): void
    {
        $this->resourceName = $this->context->getEntityName().'Resource';
        $this->generatedFiles = [];
    }

    abstract protected function getPageType(): string;

    public function generate(): void
    {
        $template = $this->getTemplate();
        $className = $this->getClassName();

        $variables = [
            'namespace' => $this->getNamespace(),
            'class_name' => $className,
            'resource_name' => $this->resourceName,
            'use_statements' => $this->formatUseStatements(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getFilePath($className), $content);
    }

    protected function getClassName(): string
    {
        return $this->getPageType().$this->context->getEntityName();
    }

    protected function getNamespace(): string
    {
        return $this->context->getNamespace('resource').'\\'.$this->resourceName.'\\Pages';
    }

    protected function getFilePath(string $className): string
    {
        $path = $this->context->getPath('resource');

        return $path.'/'.$this->resourceName.'/Pages/'.$className.'.php';
    }

    protected function getGeneratorType(): string
    {
        return 'page_'.$this->getPageType();
    }

    protected function getTemplate(): string
    {
        $template = $this->loadStub($this->getTemplate());
        if (! $template) {
            throw new RuntimeException('Failed to load template: '.$this->getTemplate());
        }

        $variables = [
            'namespace' => $this->getNamespace(),
            'class_name' => $this->getClassName(),
            'resource_name' => $this->resourceName,
            'resource_namespace' => $this->context->getNamespace('resource'),
            'model_class' => $this->context->getEntityName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'methods' => $this->formatMethods(),
        ];

        return $this->replaceTemplateVariables($template, $variables);
    }
}
