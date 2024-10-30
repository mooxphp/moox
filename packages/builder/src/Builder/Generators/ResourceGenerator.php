<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesContentCleanup;
use Moox\Builder\Builder\Traits\HandlesIndentation;
use Moox\Builder\Builder\Traits\HandlesNamespacing;
use Moox\Builder\Builder\Traits\HandlesPluralization;

class ResourceGenerator extends AbstractGenerator
{
    use HandlesContentCleanup;
    use HandlesIndentation;
    use HandlesNamespacing;
    use HandlesPluralization;

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
        $template = $this->loadStub('resource');

        $variables = [
            'namespace' => $this->getFilamentNamespace('Resources'),
            'class_name' => $this->entityName,
            'model' => $this->entityName,
            'model_plural' => $this->getPluralModelName(),
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
        $content = $this->cleanupContent($content, 'Resource');

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

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
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

    protected function getResourcePath(): string
    {
        return $this->getFilamentPath('Resources').'/'.$this->entityName.'Resource.php';
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

        $parentClass = match ($page) {
            'List' => 'ListRecords',
            'Create' => 'CreateRecord',
            'Edit' => 'EditRecord',
            'View' => 'ViewRecord',
            default => 'Record'
        };

        $variables = [
            'namespace' => str_replace('/', '\\', $this->getFilamentNamespace('Resources\\Pages')),
            'class_name' => $page.$this->entityName,
            'model' => $this->entityName,
            'model_plural' => $this->getPluralModelName(),
            'resource' => $this->entityName.'Resource',
            'use_statements' => $this->formatPageUseStatements($page),
            'traits' => $this->formatPageTraits($page),
            'methods' => $this->formatPageMethods($page),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $content = $this->cleanupContent($content, $parentClass);
        $this->writeFile($this->getPagePath($page), $content);
    }

    protected function formatPageUseStatements(string $page): string
    {
        $statements = array_merge(
            [
                'use '.$this->getFilamentNamespace('Resources').'\\'.$this->entityName.'Resource',
            ],
            $this->getUseStatements('pages', strtolower($page))
        );

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
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
        if ($page === 'List') {
            return $this->getFilamentPath('Resources/Pages').'/List'.$this->getPluralModelName().'.php';
        }

        return $this->getFilamentPath('Resources/Pages').'/'.$page.$this->entityName.'.php';
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

    protected function formatMethods(): string
    {
        $methods = $this->getMethods('resource');

        return implode("\n\n    ", array_filter($methods));
    }
}
