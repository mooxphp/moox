<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Generators;

use Moox\Builder\Builder\Traits\HandlesIndentation;

class MigrationGenerator extends AbstractGenerator
{
    use HandlesIndentation;

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
            'fields' => $this->formatMigrationContent(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $this->writeFile($this->getMigrationPath(), $content);
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

    protected function getMigrationPath(): string
    {
        return base_path('database/migrations/').date('Y_m_d_His').'_create_'.$this->getTableName().'_table.php';
    }
}
