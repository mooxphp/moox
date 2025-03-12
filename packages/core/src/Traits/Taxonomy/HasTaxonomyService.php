<?php

namespace Moox\Core\Traits\Taxonomy;

use Moox\Core\Services\TaxonomyService;

trait HasTaxonomyService
{
    protected static array $taxonomyServiceCache = [];

    protected function getTaxonomyService(): TaxonomyService
    {
        $className = static::class;

        if (! isset(static::$taxonomyServiceCache[$className])) {
            $service = app(TaxonomyService::class);
            $service->setCurrentResource($this->getResourceName());
            static::$taxonomyServiceCache[$className] = $service;
        }

        return static::$taxonomyServiceCache[$className];
    }

    public static function getTaxonomyServiceStatic(): TaxonomyService
    {
        $className = static::class;

        if (! isset(static::$taxonomyServiceCache[$className])) {
            $service = app(TaxonomyService::class);

            try {
                $reflection = new \ReflectionMethod(static::class, 'getTaxonomyModel');
                if ($reflection->isStatic()) {
                    $resourceName = class_basename(static::getTaxonomyModel());
                } else {
                    $resourceName = class_basename(static::class);
                }
            } catch (\ReflectionException $e) {
                $resourceName = class_basename(static::class);
            }

            $service->setCurrentResource($resourceName);
            static::$taxonomyServiceCache[$className] = $service;
        }

        return static::$taxonomyServiceCache[$className];
    }

    protected function getResourceName(): string
    {
        $model = static::getModel();

        return class_basename($model);
    }

    protected static function getModelStatic(): string
    {
        if (method_exists(static::class, 'getResource')) {
            return static::getResource()::getModel();
        }

        return static::getModel();
    }
}
