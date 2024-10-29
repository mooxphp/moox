<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class ModelGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub('model');

        $variables = [
            'namespace' => $this->entityNamespace,
            'class_name' => $this->entityName,
            'table' => $this->getTableName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'fillable' => $this->getFillableFields(),
            'casts' => $this->getCasts(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
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

        return implode(",\n        ", array_filter($fillable));
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

    protected function getModelPath(): string
    {
        return $this->entityPath.'/Models/'.$this->entityName.'.php';
    }
}
