<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class ModelGenerator extends AbstractGenerator
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
        $template = $this->loadStub('model');

        $variables = [
            'namespace' => 'App\\Models',
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
        $content = $this->cleanupContent($content);

        $this->writeFile($this->getModelPath(), $content);
    }

    protected function cleanupContent(string $content): string
    {
        // Remove empty lines between class opening brace and first property
        $content = preg_replace("/class (.+) extends Model\n{\n\n+/", "class $1 extends Model\n{\n", $content);

        // Remove empty casts array entirely
        $content = preg_replace("/\n\n    protected \\\$casts = \[\n        \n    \];\n/", '', $content);

        // Remove empty traits
        $content = preg_replace("/\n    \n/", "\n", $content);

        // Remove multiple empty lines
        $content = preg_replace("/\n\n\n+/", "\n\n", $content);

        // Remove empty line at the end of the class
        $content = preg_replace("/\n\n}/", "\n}", $content);

        return $content;
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
