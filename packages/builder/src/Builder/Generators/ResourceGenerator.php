<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesContentCleanup;

class ResourceGenerator extends AbstractGenerator
{
    use HandlesContentCleanup;

    public function generate(): void
    {
        $template = $this->loadStub('resource');

        $variables = [
            'namespace' => $this->context->getResourceNamespace(),
            'class_name' => $this->context->getEntityName(),
            'model' => $this->context->getEntityName(),
            'model_plural' => $this->context->getPluralModelName(),
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => $this->formatResourceUseStatements(),
            'traits' => $this->formatTraits(),
            'form_setup' => $this->getFormSetup(),
            'form_schema' => $this->getFormSchema(),
            'table_setup' => $this->getTableSetup(),
            'table_columns' => $this->getTableColumns(),
            'default_sort_column' => 'created_at',
            'default_sort_direction' => 'desc',
            'table_actions' => $this->getTableActions(),
            'table_bulk_actions' => $this->getTableBulkActions(),
            'table_filters' => $this->getTableFilters(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $content = $this->cleanupContent($content, 'Resource');

        $this->writeFile($this->context->getResourcePath(), $content);
        $this->generateResourcePages();
    }

    protected function formatResourceUseStatements(): string
    {
        $statements = array_merge(
            ['use Filament\Forms\Form;', 'use Filament\Tables\Table;'],
            $this->getUseStatements('resource', 'forms'),
            $this->getUseStatements('resource', 'columns'),
            $this->getUseStatements('resource', 'filters'),
            $this->getUseStatements('resource', 'actions')
        );

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function getFormSchema(): string
    {
        $fields = [];
        foreach ($this->blocks as $block) {
            $fields[] = $block->formField();
        }

        return $this->formatWithIndentation(array_filter($fields), 3);
    }

    protected function getTableColumns(): string
    {
        $columns = [];
        foreach ($this->blocks as $block) {
            $columns[] = $block->tableColumn();
        }

        return $this->formatWithIndentation(array_filter($columns), 3);
    }

    protected function getTableActions(): string
    {
        $actions = [];
        foreach ($this->features as $feature) {
            $actions = array_merge($actions, $feature->getActions());
        }

        return $this->formatWithIndentation(array_filter($actions), 3);
    }

    protected function getTableFilters(): string
    {
        $filters = [];
        foreach ($this->blocks as $block) {
            $filters[] = $block->tableFilter();
        }

        return $this->formatWithIndentation(array_filter($filters), 3);
    }

    protected function getNavigationIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    protected function generateResourcePages(): void
    {
        $pages = ['List', 'Create', 'Edit', 'View'];

        foreach ($pages as $page) {
            $this->generateResourcePage($page);
        }
    }

    protected function generateResourcePage(string $page): void
    {
        $template = $this->loadStub('pages/'.strtolower($page));

        $variables = [
            'namespace' => $this->context->getResourceNamespace().'\\Pages',
            'class_name' => $page.$this->context->getEntityName(),
            'model' => $this->context->getEntityName(),
            'model_plural' => $this->context->getPluralModelName(),
            'resource' => $this->context->getEntityName().'Resource',
            'use_statements' => $this->formatPageUseStatements($page),
            'traits' => $this->formatTraits('pages', strtolower($page)),
            'methods' => $this->formatMethods('pages', strtolower($page)),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $content = $this->cleanupContent($content, $page.'Records');

        $this->writeFile($this->getPagePath($page), $content);
    }

    protected function formatPageUseStatements(string $page): string
    {
        $statements = array_merge(
            [
                'use '.$this->context->getResourceNamespace().'\\'.$this->context->getEntityName().'Resource;',
            ],
            $this->getUseStatements('pages', strtolower($page))
        );

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function getPagePath(string $page): string
    {
        $basePath = dirname($this->context->getResourcePath()).'/Pages';

        if ($page === 'List') {
            return $basePath.'/List'.$this->context->getPluralModelName().'.php';
        }

        return $basePath.'/'.$page.$this->context->getEntityName().'.php';
    }

    protected function getFormSetup(): string
    {
        return '';
    }

    protected function getTableSetup(): string
    {
        return '';
    }

    protected function getTableBulkActions(): string
    {
        return '';
    }

    protected function getGeneratorType(): string
    {
        return 'resource';
    }
}
