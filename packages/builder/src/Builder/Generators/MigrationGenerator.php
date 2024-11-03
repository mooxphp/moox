<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesIndentation;

class MigrationGenerator extends AbstractGenerator
{
    use HandlesIndentation;

    public function generate(): void
    {
        $template = $this->loadStub('migration');

        $variables = [
            'table' => $this->context->getTableName(),
            'fields' => $this->formatMigrationContent(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->context->getMigrationPath(), $content);
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
        foreach ($this->blocks as $block) {
            if (method_exists($block, 'migration')) {
                $blockFields = $block->migration();
                if (is_string($blockFields)) {
                    $blockFields = explode(PHP_EOL, $blockFields);
                }
                $fields = array_merge(
                    $fields,
                    array_map(
                        fn ($field) => rtrim($field, ';').';',
                        is_array($blockFields) ? $blockFields : [$blockFields]
                    )
                );
            } else {
                $type = $block->getMigrationType();
                $fields[] = '$table->'.$type.'(\''.$block->getName().'\').';
            }
        }

        return $fields;
    }

    protected function getFeatureFields(): array
    {
        $fields = [];
        foreach ($this->features as $feature) {
            $migrations = $feature->getMigrations();
            if (! empty($migrations)) {
                $fields = array_merge($fields, $migrations);
            }
        }

        return $fields;
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

    protected function getGeneratorType(): string
    {
        return 'migration';
    }
}
