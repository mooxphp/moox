<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Illuminate\Support\Str;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\File\FileManager;

class ModelGenerator extends AbstractGenerator
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
        if (! $template) {
            throw new \Exception('Failed to load template: '.$this->getTemplate());
        }

        $variables = [
            'namespace' => $this->context->formatNamespace('model', false),
            'class_name' => $this->context->getEntityName(),
            'table' => $this->context->getTableName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'fillable' => $this->getFillable(),
            'casts' => $this->getCasts(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->context->getPath('model').'/'.$this->context->getEntityName().'.php';
        $this->writeFile($path, $content);
    }

    protected function getFillable(): string
    {
        $fillable = [];
        foreach ($this->getBlocks() as $block) {
            if ($block->isFillable()) {
                $fillable = array_merge($fillable, $block->getFillableFields());
            }
        }

        return implode(",\n        ", array_map(fn ($field) => "'$field'", $fillable));
    }

    protected function getCasts(): string
    {
        $casts = [];
        foreach ($this->getBlocks() as $block) {
            $cast = $block->modelCast();
            if (! empty($cast)) {
                $cast = preg_replace("/['\"](.*?)['\"]\s*=>\s*['\"](.*?)['\"]/", "'$1' => '$2'", $cast);
                $casts[] = $cast;
            }
        }

        return implode(",\n        ", array_filter($casts));
    }

    protected function getGeneratorType(): string
    {
        return 'model';
    }

    protected function formatUseStatements(): string
    {
        $statements = $this->getUseStatements('model');

        foreach ($this->getBlocks() as $block) {
            $blockTraits = $block->getTraits('model');
            if (! empty($blockTraits)) {
                foreach ($blockTraits as $trait) {
                    if (! in_array("use $trait;", $statements)) {
                        $statements[] = "use $trait;";
                    }
                }
            }
        }

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function formatMethods(): string
    {
        $methods = [];
        $methodNames = [];

        foreach ($this->getBlocks() as $block) {
            if ($blockMethods = $block->getMethods('model')) {
                foreach ($blockMethods as $method) {
                    if (is_array($method)) {
                        foreach ($method as $subMethod) {
                            if (! is_string($subMethod)) {
                                continue;
                            }
                            if (preg_match('/protected function ([a-zA-Z]+)\(/', $subMethod, $matches)) {
                                $methodName = $matches[1];
                                if (! in_array($methodName, $methodNames)) {
                                    $methodNames[] = $methodName;
                                    $methods[] = str_replace(
                                        '{{ resource_name }}',
                                        Str::kebab($this->context->getEntityName()),
                                        $subMethod
                                    );
                                }
                            }
                        }
                    } elseif (is_string($method)) {
                        if (preg_match('/protected function ([a-zA-Z]+)\(/', $method, $matches)) {
                            $methodName = $matches[1];
                            if (! in_array($methodName, $methodNames)) {
                                $methodNames[] = $methodName;
                                $methods[] = str_replace(
                                    '{{ resource_name }}',
                                    Str::kebab($this->context->getEntityName()),
                                    $method
                                );
                            }
                        }
                    }
                }
            }
        }

        return implode("\n\n    ", array_filter($methods));
    }
}
