<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

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

        $variables = [
            'namespace' => $this->context->getNamespace('model'),
            'class_name' => $this->context->getEntityName(),
            'table' => $this->context->getTableName(),
            'use_statements' => $this->formatUseStatements(),
            'traits' => $this->formatTraits(),
            'fillable' => $this->getFillable(),
            'casts' => $this->getCasts(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile(
            $this->context->getPath('model').'/'.$this->context->getEntityName().'.php',
            $content
        );
    }

    protected function getFillable(): string
    {
        $fillable = [];
        foreach ($this->getBlocks() as $block) {
            if ($block->isFillable()) {
                $fillable[] = "'".$block->getName()."'";
            }
        }

        return implode(",\n        ", $fillable);
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
}
