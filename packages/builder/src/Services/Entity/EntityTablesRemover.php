<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Entity;

use Illuminate\Support\Facades\Schema;

class EntityTablesRemover extends AbstractEntityService
{
    public function execute(): void
    {
        $this->ensureContextIsSet();
        Schema::dropIfExists($this->context->getTableName());
    }
}
