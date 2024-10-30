<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesContentCleanup;
use Moox\Builder\Builder\Traits\HandlesIndentation;
use Moox\Builder\Builder\Traits\HandlesNamespacing;

class ModelGenerator extends AbstractGenerator
{
    use HandlesContentCleanup;
    use HandlesIndentation;
    use HandlesNamespacing;

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
        $template = $this->loadStub('model');

        $variables = [
            'namespace' => $this->getModelNamespace(),
            'class_name' => $this->entityName,
            'table' => $this->getTableName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'fillable' => $this->getFillableFields(),
            'casts' => $this->getCasts(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);

        // Post-process the content to remove unwanted empty lines
        $content = $this->cleanupContent($content, 'Model');

        $this->writeFile($this->getModelPath(), $content);
    }

    protected function getTableName(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->entityName)).'s';
    }

    protected function formatUseStatements(): string
    {
        $statements = $this->getUseStatements('model');

        return implode("\n", $statements);
    }

    protected function formatTraits(): string
    {
        $traits = $this->getTraits('model');
        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', $traits).';';
    }

    protected function getFillableFields(): string
    {
        $fillable = [];
        foreach ($this->blocks as $block) {
            $fillable[] = $block->modelAttribute();
        }

        return $this->formatWithIndentation(array_filter($fillable), 2);
    }

    protected function getCasts(): string
    {
        $casts = [];
        foreach ($this->blocks as $block) {
            $cast = $block->modelCast();
            if (! empty($cast)) {
                $casts[] = $cast;
            }
        }

        return implode(",\n        ", array_filter($casts));
    }

    protected function formatMethods(): string
    {
        $methods = $this->getMethods('model');

        return implode("\n\n    ", array_filter($methods));
    }

    protected function getModelNamespace(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityNamespace;
        }

        return 'App\\Models';
    }

    protected function getModelPath(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityPath.'/'.$this->entityName.'.php';
        }

        return $this->entityPath.'/Models/'.$this->entityName.'.php';
    }
}
