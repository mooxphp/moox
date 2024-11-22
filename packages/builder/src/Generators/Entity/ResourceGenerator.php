<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Generators\Entity\Pages\CreatePageGenerator;
use Moox\Builder\Generators\Entity\Pages\EditPageGenerator;
use Moox\Builder\Generators\Entity\Pages\ListPageGenerator;
use Moox\Builder\Generators\Entity\Pages\ViewPageGenerator;
use Moox\Builder\Services\File\FileManager;
use RuntimeException;

class ResourceGenerator extends AbstractGenerator
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
            throw new RuntimeException('Failed to load template: '.$this->getTemplate());
        }

        $variables = [
            'namespace' => $this->context->getNamespace('resource'),
            'class_name' => $this->context->getEntityName(),
            'model' => $this->context->getNamespace('model').'\\'.$this->context->getEntityName(),
            'navigation_group' => $this->getNavigationGroup(),
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => $this->formatResourceUseStatements(),
            'form_schema' => $this->getFormSchema(),
            'table_columns' => $this->getTableColumns(),
            'table_filters' => $this->getTableFilters(),
            'table_actions' => $this->getTableActions(),
            'table_bulk_actions' => $this->getTableBulkActions(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->context->getPath('resource').'/'.
            $this->context->getEntityName().'Resource.php';

        $this->writeFile($path, $content);
        $this->generateResourcePages();
    }

    protected function generateResourcePages(): void
    {
        $pageGenerators = [
            ListPageGenerator::class,
            CreatePageGenerator::class,
            EditPageGenerator::class,
            ViewPageGenerator::class,
        ];

        foreach ($pageGenerators as $generatorClass) {
            $generator = new $generatorClass(
                $this->context,
                $this->fileManager,
                $this->getBlocks()
            );
            $generator->generate();
        }
    }

    protected function getNavigationIcon(): string
    {
        return config('builder.generators.resource.navigation_icon', 'heroicon-o-rectangle-stack');
    }

    protected function getGeneratorType(): string
    {
        return 'resource';
    }

    protected function formatResourceUseStatements(): string
    {
        $statements = array_merge(
            [
                'use Filament\Forms\Form;',
                'use Filament\Tables\Table;',
                'use '.$this->context->getNamespace('resource').'\\'.$this->context->getEntityName().'Resource\\Pages;',
            ],
            $this->getUseStatements('resource', 'forms'),
            $this->getUseStatements('resource', 'columns'),
            $this->getUseStatements('resource', 'filters'),
            $this->getUseStatements('resource', 'actions'),
            $this->getUseStatements('resource', 'traits')
        );

        foreach ($this->getBlocks() as $block) {
            if (! empty($block->traits['resource'])) {
                foreach ($block->traits['resource'] as $trait) {
                    $statements[] = 'use Moox\Core\Traits\\'.$trait.';';
                }
            }
        }

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function getFormSchema(): string
    {
        $fields = [];
        foreach ($this->getBlocks() as $block) {
            $field = rtrim($block->formField(), ',');
            $fields[] = $field;
        }

        return implode(",\n            ", $fields);
    }

    protected function getTableColumns(): string
    {
        $columns = [];
        foreach ($this->getBlocks() as $block) {
            $column = rtrim($block->tableColumn(), ',');
            $columns[] = $column;
        }

        return implode(",\n            ", $columns);
    }

    protected function getTableActions(): string
    {
        $actions = [];
        foreach ($this->getBlocks() as $block) {
            $blockActions = $block->getTableActions();
            if (! empty($blockActions)) {
                $actions = array_merge($actions, $blockActions);
            }
        }

        return implode(",\n            ", $actions);
    }

    protected function getTableFilters(): string
    {
        $filters = [];
        foreach ($this->getBlocks() as $block) {
            $blockFilters = $block->getTableFilters();
            if (! empty($blockFilters)) {
                $filters = array_merge($filters, $blockFilters);
            }
        }

        return implode(",\n            ", $filters);
    }

    protected function getTableBulkActions(): string
    {
        $actions = [];
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getTableBulkActions')) {
                $blockActions = $block->getTableBulkActions();
                if (! empty($blockActions)) {
                    $actions = array_merge($actions, $blockActions);
                }
            }
        }

        return implode(",\n            ", $actions);
    }

    protected function getNavigationGroup(): string
    {
        return match ($this->context->getContextType()) {
            'preview' => 'Previews',
            'package' => $this->context->getEntityName(),
            default => 'Content'
        };
    }
}
