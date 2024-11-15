<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\Schema;

class EntityTablesRemover extends ContextAwareService
{
    public function execute(): void
    {
        Schema::dropIfExists($this->context->getTableName());
    }
}
