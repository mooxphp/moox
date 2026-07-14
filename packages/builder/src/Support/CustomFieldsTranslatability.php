<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Moox\Builder\Concerns\HasCustomFields;
use Moox\Builder\Registry\EntityRegistry;

final class CustomFieldsTranslatability
{
    /**
     * @param  class-string  $resourceClass
     */
    public function forResource(string $resourceClass): bool
    {
        if (! in_array(HasCustomFields::class, class_uses_recursive($resourceClass), true)) {
            return false;
        }

        if (method_exists($resourceClass, 'customFieldsAreTranslatable')) {
            return (bool) $resourceClass::customFieldsAreTranslatable();
        }

        if (! method_exists($resourceClass, 'getModel')) {
            return false;
        }

        $model = $resourceClass::getModel();

        return is_string($model) && $this->forModel($model);
    }

    public function forEntity(string $entity): bool
    {
        $resource = app(EntityRegistry::class)->resourceFor($entity);

        if ($resource !== null) {
            return $this->forResource($resource);
        }

        return false;
    }

    /**
     * @param  class-string|null  $modelClass
     * @param  class-string|null  $resourceClass
     */
    public function valuesAreTranslatable(
        string $entity,
        ?string $modelClass = null,
        ?string $resourceClass = null,
    ): bool {
        if ($resourceClass !== null && $this->forResource($resourceClass)) {
            return true;
        }

        if ($modelClass !== null && $this->forModel($modelClass)) {
            return true;
        }

        return $this->forEntity($entity);
    }

    /**
     * @param  class-string  $modelClass
     */
    public function forModel(string $modelClass): bool
    {
        if (method_exists($modelClass, 'customFieldsAreTranslatable')) {
            return (bool) $modelClass::customFieldsAreTranslatable();
        }

        return is_subclass_of($modelClass, TranslatableContract::class);
    }
}
