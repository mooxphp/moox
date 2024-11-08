<?php

declare(strict_types=1);

namespace Moox\Builder\Generators\Entity;

class MigrationGenerator extends AbstractGenerator
{
    protected string $migrationFileName;

    public function generate(): void
    {
        $this->migrationFileName = $this->generateMigrationFileName();
        $template = $this->loadStub($this->getTemplate());
        $variables = [
            'class' => $this->getMigrationClassName(),
            'table' => $this->context->getTableName(),
            'fields' => $this->formatFields(),
        ];

        $content = $this->replaceTemplateVariables($template, $variables);
        $path = $this->context->getPath('migration').'/'.$this->migrationFileName;
        if ($this->context->getCommand()) {
            $this->context->getCommand()->info('Generating migration at: '.$path);
        }
        $this->writeFile($path, $content);
    }

    protected function generateMigrationFileName(): string
    {
        $tableName = $this->context->getTableName();

        return match ($this->context->getContextType()) {
            'app' => date('Y_m_d_His').'_create_'.$tableName.'_table.php',
            'package' => 'create_'.$tableName.'_table.php.stub',
            'preview' => 'preview_'.date('Y_m_d_His').'_create_'.$tableName.'_table.php',
            default => throw new \InvalidArgumentException('Invalid context type: '.$this->context->getContextType()),
        };
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
