<?php

declare(strict_types=1);

namespace Moox\Builder\Generators;

class MigrationGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub($this->getTemplate());
        $variables = [
            'class' => $this->getMigrationClassName(),
            'table' => $this->context->getTableName(),
            'fields' => $this->formatFields(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile(
            $this->context->getPath('migration').'/create_'.$this->context->getTableName().'_table.php',
            $content
        );
    }

    protected function getGeneratorType(): string
    {
        return 'migration';
    }

    protected function getMigrationClassName(): string
    {
        return 'Create'.$this->context->getPluralModelName().'Table';
    }

    protected function formatFields(): string
    {
        $lines = array_merge(
            $this->getBaseFields(),
            $this->getCustomFields()
        );

        return $this->formatWithIndentation($lines, 3, "\n");
    }

    protected function getBaseFields(): array
    {
        return [
            '$table->id();',
            '$table->timestamps();',
        ];
    }

    protected function getCustomFields(): array
    {
        $fields = [];
        foreach ($this->getBlocks() as $block) {
            $migration = $block->migration();
            if (! empty($migration)) {
                if (is_array($migration)) {
                    $fields = array_merge($fields, array_map(function ($field) {
                        return rtrim(trim($field), ';').';';
                    }, $migration));
                } else {
                    $fields[] = rtrim(trim($migration), ';').';';
                }
            }
        }

        return array_filter($fields);
    }
}
