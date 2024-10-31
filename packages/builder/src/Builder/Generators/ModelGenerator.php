<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesContentCleanup;

class ModelGenerator extends AbstractGenerator
{
    use HandlesContentCleanup;

    public function generate(): void
    {
        $template = $this->loadStub('model');

        $variables = [
            'namespace' => $this->context->getModelNamespace(),
            'class_name' => $this->context->getEntityName(),
            'table' => $this->context->getTableName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'fillable' => $this->getFillableFields(),
            'casts' => $this->getCasts(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $content = $this->cleanupContent($content, 'Model');

        $this->writeFile($this->context->getModelPath(), $content);
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

    protected function getGeneratorType(): string
    {
        return 'model';
    }
}
