<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class ResourceGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub('resource');

        $variables = [
            'namespace' => $this->entityNamespace.'\\Resources',
            'class_name' => $this->entityName,
            'model' => $this->entityName,
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => $this->formatUseStatements(),
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
        $this->writeFile($this->getResourcePath(), $content);

        $this->generateResourcePages();
    }

    protected function formatUseStatements(): string
    {
        $statements = array_merge(
            ['use Filament\Forms\Form;', 'use Filament\Tables\Table;'],
            $this->getUseStatements('resource', 'forms'),
            $this->getUseStatements('resource', 'columns'),
            $this->getUseStatements('resource', 'filters'),
            $this->getUseStatements('resource', 'actions')
        );

        return implode("\n", array_unique($statements));
    }

    protected function formatTraits(): string
    {
        $traits = $this->getTraits('resource');
        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', $traits).';';
    }

    protected function getFormSchema(): string
    {
        $fields = [];
        foreach ($this->blocks as $block) {
            $fields[] = $block->formField();
        }

        return implode(",\n                ", array_filter($fields));
    }

    protected function getTableColumns(): string
    {
        $columns = [];
        foreach ($this->blocks as $block) {
            $columns[] = $block->tableColumn();
        }

        return implode(",\n                ", array_filter($columns));
    }

    protected function getTableActions(): string
    {
        $actions = [];
        foreach ($this->features as $feature) {
            $actions = array_merge($actions, $feature->getActions());
        }

        return implode(",\n                ", array_filter($actions));
    }

    protected function getTableFilters(): string
    {
        $filters = [];
        foreach ($this->blocks as $block) {
            $filters[] = $block->tableFilter();
        }

        return implode(",\n                ", array_filter($filters));
    }

    protected function getNavigationIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    protected function getResourcePath(): string
    {
        return $this->entityPath.'/Resources/'.$this->entityName.'Resource.php';
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
            'namespace' => $this->entityNamespace.'\\Resources\\Pages',
            'class_name' => $page.$this->entityName,
            'resource' => $this->entityName.'Resource',
            'traits' => $this->formatPageTraits($page),
            'methods' => $this->formatPageMethods($page),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getPagePath($page), $content);
    }

    protected function formatPageTraits(string $page): string
    {
        $traits = $this->getTraits('pages', strtolower($page));
        if (empty($traits)) {
            return '';
        }

        return 'use '.implode(', ', $traits).';';
    }

    protected function formatPageMethods(string $page): string
    {
        $methods = $this->getMethods('pages', strtolower($page));

        return implode("\n\n    ", array_filter($methods));
    }

    protected function getPagePath(string $page): string
    {
        return $this->entityPath.'/Resources/Pages/'.$page.$this->entityName.'.php';
    }

    protected function getFormSetup(): string
    {
        // Implement logic to set up the form, if needed
        return '';
    }

    protected function getTableSetup(): string
    {
        // Implement logic to set up the table, if needed
        return '';
    }

    protected function getTableBulkActions(): string
    {
        // Implement logic to get table bulk actions, if needed
        return '';
    }

    protected function formatMethods(): string
    {
        $methods = $this->getMethods('resource');

        return implode("\n\n    ", array_filter($methods));
    }
}
