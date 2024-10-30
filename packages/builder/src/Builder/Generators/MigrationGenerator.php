<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesIndentation;
use Moox\Builder\Builder\Traits\HandlesNamespacing;

class MigrationGenerator extends AbstractGenerator
{
    use HandlesIndentation;
    use HandlesNamespacing;

    public function generate(): void
    {
        $template = $this->loadStub('migration');

        $variables = [
            'table' => $this->getTableName(),
            'fields' => $this->formatMigrationContent(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getMigrationPath(), $content);
    }

    protected function getMigrationPath(): string
    {
        if ($this->isPackageContext()) {
            return $this->entityPath.'/database/migrations/create_'.$this->getTableName().'_table.php.stub';
        }

        return base_path('database/migrations/').date('Y_m_d_His').'_create_'.$this->getTableName().'_table.php';
    }

    protected function getTableName(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->entityName)).'s';
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
        foreach ($this->blocks as $block) {
            if (method_exists($block, 'migration')) {
                $blockFields = $block->migration();
                if (is_array($blockFields)) {
                    $fields = array_merge($fields, $blockFields);
                } else {
                    $fields[] = $blockFields;
                }
            } else {
                $type = $block->getMigrationType();
                $fields[] = '$table->'.$type.'(\''.$block->getName().'\');';
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
}
