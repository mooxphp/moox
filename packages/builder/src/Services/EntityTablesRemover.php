<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\Schema;

class EntityTablesRemover extends AbstractService
{
    public function execute(): void
    {
        Schema::dropIfExists($this->context->getTableName());
    }
}
