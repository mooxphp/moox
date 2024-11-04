<?php

declare(strict_types=1);

namespace Moox\Builder\Generators;

class MigrationGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());

        $variables = [
            'table' => $this->context->getTableName(),
            'fields' => $this->formatMigrationContent(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->context->getPath('migration'), $content);
    }

    protected function formatMigrationContent(): string
    {
        $lines = array_merge(
            $this->getBaseFields(),
            $this->getCustomFields(),
            $this->getFeatureFields()
        );

        return $this->formatWithIndentation($lines);
    }

    protected function getBaseFields(): array
    {
        return [
            '$table->id()',
            '$table->timestamps()',
        ];
    }

    protected function getCustomFields(): array
    {
        return array_filter(array_map(function ($block) {
            return $block->migration();
        }, $this->blocks));
    }

    protected function getFeatureFields(): array
    {
        return array_filter(array_merge(...array_map(function ($feature) {
            return $feature->getMigrations();
        }, $this->features)));
    }

    protected function getGeneratorType(): string
    {
        return 'migration';
    }
}
