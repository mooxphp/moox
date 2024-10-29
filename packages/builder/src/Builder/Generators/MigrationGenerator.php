<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class MigrationGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $template = $this->loadStub('migration');

        $variables = [
            'table' => $this->getTableName(),
            'base_fields' => $this->getBaseFields(),
            'custom_fields' => $this->getCustomFields(),
            'feature_fields' => $this->getFeatureFields(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getMigrationPath(), $content);
    }

    protected function getTableName(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->entityName)).'s';
    }

    protected function getBaseFields(): string
    {
        return '';
    }

    protected function getCustomFields(): string
    {
        $fields = [];
        foreach ($this->blocks as $block) {
            $type = $block->getMigrationType();
            if ($type === 'string') {
                $fields[] = '$table->string(\''.$block->getName().'\')';
            } elseif ($type === 'text') {
                $fields[] = '$table->text(\''.$block->getName().'\')';
            }
            // Add more field types as needed
        }

        return implode(";\n            ", array_filter($fields)).';';
    }

    protected function getFeatureFields(): string
    {
        $fields = [];
        foreach ($this->features as $feature) {
            $migrations = $feature->getMigrations();
            if (! empty($migrations)) {
                $fields[] = '$table->'.implode(";\n            \$table->", $migrations);
            }
        }

        return implode(";\n            ", array_filter($fields)).';';
    }

    protected function getMigrationPath(): string
    {
        return $this->entityPath.'/Database/Migrations/'.date('Y_m_d_His').'_create_'.$this->getTableName().'_table.php';
    }
}
