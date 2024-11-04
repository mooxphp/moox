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
            $this->getCustomFields()
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
        $fields = [];
        foreach ($this->getBlocks() as $block) {
            $migration = $block->migration();
            if (! empty($migration)) {
                if (is_array($migration)) {
                    $fields = array_merge($fields, $migration);
                } else {
                    $fields[] = $migration;
                }
            }
        }

        return array_filter($fields);
    }

    protected function getGeneratorType(): string
    {
        return 'migration';
    }
}
