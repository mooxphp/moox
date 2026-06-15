<?php

declare(strict_types=1);

namespace Moox\Builder\Observers;

use Moox\Builder\Registry\DefinitionRegistry;

class InvalidateDefinitionCacheObserver
{
    public function saved(object $model): void
    {
        app(DefinitionRegistry::class)->forget();
    }

    public function deleted(object $model): void
    {
        app(DefinitionRegistry::class)->forget();
    }
}
