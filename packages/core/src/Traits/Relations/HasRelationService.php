<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Relations;

use Moox\Core\Services\RelationService;

trait HasRelationService
{
    protected function relationService(): RelationService
    {
        if (! method_exists(static::class, 'getResourceName')) {
            throw new \LogicException(sprintf('Model %s must implement static getResourceName().', static::class));
        }

        return app(RelationService::class)->forResource(static::getResourceName());
    }

    protected static function relationServiceFor(string $resource): RelationService
    {
        return app(RelationService::class)->forResource($resource);
    }
}
