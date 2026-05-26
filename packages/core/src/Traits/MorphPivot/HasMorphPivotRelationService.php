<?php

declare(strict_types=1);

namespace Moox\Core\Traits\MorphPivot;

use Moox\Core\Services\MorphPivotRelationService;

trait HasMorphPivotRelationService
{
    /** @var array<class-string, MorphPivotRelationService> */
    protected static array $morphPivotRelationServiceCache = [];

    protected function getMorphPivotRelationService(): MorphPivotRelationService
    {
        $className = static::class;

        if (! isset(static::$morphPivotRelationServiceCache[$className])) {
            $service = app(MorphPivotRelationService::class);
            $service->setCurrentResource(static::getResourceName());
            static::$morphPivotRelationServiceCache[$className] = $service;
        }

        return static::$morphPivotRelationServiceCache[$className];
    }
}
