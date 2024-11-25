<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Preview;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Services\ContextAwareService;

class PreviewTableManager extends ContextAwareService
{
    protected array $blocks = [];

    public function setBlocks(array $blocks): self
    {
        $this->blocks = $blocks;

        return $this;
    }

    public function withContext(BuildContext $context): self
    {
        $this->setContext($context);

        return $this;
    }

    public function execute(): void
    {
        $this->ensureContextIsSet();

        if ($this->context->getContextType() !== 'preview') {
            return;
        }

        $tableName = $this->context->getTableName();
        $this->dropTableIfExists($tableName);

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            foreach ($this->blocks as $block) {
                if (method_exists($block, 'migration')) {
                    $migration = $block->migration();
                    if (is_array($migration)) {
                        foreach ($migration as $field) {
                            $this->addField($table, $field);
                        }
                    }
                }
            }

            $table->timestamps();
        });
    }

    protected function addField(Blueprint $table, string $field): void
    {
        $field = trim(rtrim($field, ';'));
        if (! empty($field)) {
            $field = str_replace('$table->', '', $field);
            eval('$table->'.$field.';');
        }
    }

    protected function dropTableIfExists(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }
    }
}
