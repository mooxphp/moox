<?php

declare(strict_types=1);

namespace Moox\Builder\Generators;

use Moox\Builder\Contexts\BuildContext;

class ResourceGenerator extends AbstractGenerator
{
    public function __construct(BuildContext $context, array $blocks = [])
    {
        parent::__construct($context, $blocks);
    }

    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());
        $modelNamespace = $this->context->getNamespace('model');
        $modelClass = $this->context->getEntityName();

        $variables = [
            'namespace' => $this->context->getNamespace('resource'),
            'class_name' => $modelClass,
            'model' => $modelClass,
            'model_namespace' => $modelNamespace,
            'model_plural' => $this->context->getPluralModelName(),
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => "use {$modelNamespace}\\{$modelClass};\n".$this->formatResourceUseStatements(),
            'traits' => $this->formatTraits(),
            'form_setup' => $this->getFormSetup(),
            'form_schema' => $this->getFormSchema(),
            'table_setup' => $this->getTableSetup(),
            'table_columns' => $this->getTableColumns(),
            'default_sort_column' => $this->getDefaultSortColumn(),
            'default_sort_direction' => $this->getDefaultSortDirection(),
            'table_actions' => $this->getTableActions(),
            'table_bulk_actions' => $this->getTableBulkActions(),
            'table_filters' => $this->getTableFilters(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile(
            $this->context->getPath('resource').'/'.$this->context->getEntityName().'Resource.php',
            $content
        );
        $this->generateResourcePages();
        $this->formatGeneratedFiles();
    }

    protected function generateResourcePages(): void
    {
        $pages = ['List', 'Create', 'Edit', 'View'];
        $resourceName = $this->context->getEntityName().'Resource';
        $basePath = $this->context->getPath('resource').'/'.$resourceName.'/Pages';

        foreach ($pages as $page) {
            $template = $this->loadStub($this->context->getPageTemplate('resource', $page));

            $className = $page === 'List'
                ? $page.$this->context->getPluralModelName()
                : $page.$this->context->getEntityName();

            $variables = [
                'namespace' => $this->context->getNamespace('resource').'\\'.$resourceName.'\\Pages',
                'resource_namespace' => $this->context->getNamespace('resource'),
                'resource_class' => $resourceName,
                'class_name' => $className,
                'model' => $this->context->getEntityName(),
                'model_plural' => $this->context->getPluralModelName(),
                'use_statements' => $this->formatPageUseStatements($page),
                'traits' => $this->formatPageTraits($page),
                'methods' => $this->formatPageMethods($page),
                'resource' => $resourceName,
            ];

            $content = $this->replaceTemplateVariables($template, $variables);
            $this->writeFile($basePath.'/'.$className.'.php', $content);
        }
    }

    protected function formatPageUseStatements(string $page): string
    {
        $statements = $this->getUseStatements('resource', "pages.{$page}");
        $statements[] = $this->context->getNamespace('resource').'\\'.$this->context->getEntityName().'Resource';

        // Add page-specific use statements from blocks
        foreach ($this->getBlocks() as $block) {
            $pageStatements = $block->getPageUseStatements(strtolower($page));
            if (! empty($pageStatements)) {
                $statements = array_merge($statements, $pageStatements);
            }
        }

        $statements = array_unique($statements);

        $statements = array_filter($statements, function ($statement) {
            return ! str_contains($statement, 'Filament\Resources\Pages');
        });

        return implode("\n", array_map(function ($statement) {
            $statement = trim($statement, '\\; ');
            $statement = str_replace('use ', '', $statement);

            return 'use '.$statement.';';
        }, $statements));
    }

    protected function formatPageTraits(string $page): string
    {
        $traits = [];
        foreach ($this->getBlocks() as $block) {
            if (! empty($block->traits['pages'][strtolower($page)])) {
                $traits = array_merge($traits, $block->traits['pages'][strtolower($page)]);
            }
        }

        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', array_unique($traits)).';';
    }

    protected function formatPageMethods(string $page): string
    {
        $methods = [];
        foreach ($this->getBlocks() as $block) {
            $blockMethods = $block->getMethods($this->getGeneratorType());
            if (! empty($blockMethods)) {
                $methods = array_merge($methods, $blockMethods);
            }
        }

        if (empty($methods)) {
            return '';
        }

        return implode("\n\n", array_unique($methods));
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

    protected function getFormSetup(): string
    {
        $setup = [];
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getFormSetup')) {
                $blockSetup = $block->getFormSetup();
                if (! empty($blockSetup)) {
                    $setup[] = $blockSetup;
                }
            }
        }

        return implode("\n        ", $setup);
    }

    protected function getTableSetup(): string
    {
        $setup = [];
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getTableSetup')) {
                $blockSetup = $block->getTableSetup();
                if (! empty($blockSetup)) {
                    $setup[] = $blockSetup;
                }
            }
        }

        return implode("\n        ", $setup);
    }

    protected function getDefaultSortColumn(): string
    {
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getDefaultSortColumn')) {
                $column = $block->getDefaultSortColumn();
                if (! empty($column)) {
                    return $column;
                }
            }
        }

        return '';
    }

    protected function getDefaultSortDirection(): string
    {
        foreach ($this->getBlocks() as $block) {
            if (method_exists($block, 'getDefaultSortDirection')) {
                $direction = $block->getDefaultSortDirection();
                if (! empty($direction)) {
                    return $direction;
                }
            }
        }

        return '';
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
}
