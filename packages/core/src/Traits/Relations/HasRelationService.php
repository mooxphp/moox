<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Relations;

use Moox\Core\Services\RelationService;

trait HasRelationService
{
    /** @var array<class-string, RelationService> */
    protected static array $relationServiceCache = [];

    protected function relationService(): RelationService
    {
        $className = static::class;

        if (! isset(static::$relationServiceCache[$className])) {
            if (! method_exists(static::class, 'getResourceName')) {
                throw new \LogicException(sprintf('Model %s must implement static getResourceName().', static::class));
            }

            $service = app(RelationService::class);
            $service->forResource(static::getResourceName());
            static::$relationServiceCache[$className] = $service;
        }

        return static::$relationServiceCache[$className];
    }

    protected static function relationServiceFor(string $resource): RelationService
    {
        return app(RelationService::class)->forResource($resource);
    }
}
