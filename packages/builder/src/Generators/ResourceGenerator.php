<?php

declare(strict_types=1);

namespace Moox\Builder\Generators;

class ResourceGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'namespace' => $this->context->getNamespace('resource'),
            'class_name' => $this->context->getEntityName(),
            'model' => $this->context->getEntityName(),
            'model_plural' => $this->context->getPluralModelName(),
            'navigation_icon' => $this->getNavigationIcon(),
            'use_statements' => $this->formatResourceUseStatements(),
            'traits' => $this->formatTraits(),
            'form_schema' => $this->getFormSchema(),
            'table_columns' => $this->getTableColumns(),
            'table_actions' => $this->getTableActions(),
            'table_filters' => $this->getTableFilters(),
            'methods' => $this->formatMethods(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->context->getPath('resource'), $content);
        $this->generateResourcePages();
    }

    protected function generateResourcePages(): void
    {
        $pages = ['List', 'Create', 'Edit'];
        $basePath = dirname($this->context->getPath('resource')).'/Pages';

        foreach ($pages as $page) {
            $template = $this->loadStub("resource-{$page}");

            $variables = [
                'namespace' => $this->context->getNamespace('resource').'\Pages',
                'resource_namespace' => $this->context->getNamespace('resource'),
                'resource_class' => $this->context->getEntityName().'Resource',
                'class_name' => $page.$this->context->getEntityName(),
                'model' => $this->context->getEntityName(),
                'model_plural' => $this->context->getPluralModelName(),
                'use_statements' => $this->formatPageUseStatements($page),
                'traits' => $this->formatPageTraits($page),
                'methods' => $this->formatPageMethods($page),
            ];

            $content = $this->replaceTemplateVariables($template, $variables);
            $this->writeFile($basePath.'/'.$page.$this->context->getEntityName().'.php', $content);
        }
    }

    protected function formatPageUseStatements(string $page): string
    {
        $statements = array_merge(
            $this->getUseStatements('resource', "pages.{$page}"),
            [
                'use '.$this->context->getNamespace('resource').'\\'.$this->context->getEntityName().'Resource',
                'use Filament\Resources\Pages\\'.($page === 'List' ? 'ListRecords' : $page.'Record'),
            ]
        );

        return implode("\n", array_map(function ($statement) {
            return rtrim($statement, ';').';';
        }, array_unique($statements)));
    }

    protected function formatPageTraits(string $page): string
    {
        $traits = [];
        foreach ($this->features as $feature) {
            $featureTraits = $feature->getTraits("resource.pages.{$page}");
            if (! empty($featureTraits)) {
                $traits = array_merge($traits, $featureTraits);
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
        foreach ($this->features as $feature) {
            $featureMethods = $feature->getMethods("resource.pages.{$page}");
            if (! empty($featureMethods)) {
                $methods = array_merge($methods, $featureMethods);
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
            $field = rtrim($block->formField(), ',');
            $fields[] = $field;
        }

        return implode(",\n            ", $fields);
    }

    protected function getTableColumns(): string
    {
        $columns = [];
        foreach ($this->blocks as $block) {
            $column = rtrim($block->tableColumn(), ',');
            $columns[] = $column;
        }

        return implode(",\n            ", $columns);
    }

    protected function getTableActions(): string
    {
        $actions = [];
        foreach ($this->features as $feature) {
            $featureActions = $feature->getTableActions();
            if (! empty($featureActions)) {
                $actions = array_merge($actions, $featureActions);
            }
        }

        return implode(",\n            ", $actions);
    }

    protected function getTableFilters(): string
    {
        $filters = [];
        foreach ($this->features as $feature) {
            $featureFilters = $feature->getTableFilters();
            if (! empty($featureFilters)) {
                $filters = array_merge($filters, $featureFilters);
            }
        }

        return implode(",\n            ", $filters);
    }
}
