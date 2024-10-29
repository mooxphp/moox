<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

class MigrationGenerator extends AbstractGenerator
{
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
        return '
            $table->id();
            $table->timestamps();
        ';
    }

    protected function getCustomFields(): string
    {
        $fields = [];
        foreach ($this->blocks as $block) {
            if (method_exists($block, 'migration')) {
                $fields[] = $block->migration();
            } else {
                $type = $block->getMigrationType();
                $fields[] = '$table->'.$type.'(\''.$block->getName().'\');';
            }
        }

        return implode("\n            ", array_filter($fields));
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
        return base_path('database/migrations/').date('Y_m_d_His').'_create_'.$this->getTableName().'_table.php';
    }
}
