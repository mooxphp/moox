<?php

declare(strict_types=1);

namespace Moox\Builder\Http\Resources\Concerns;

use Moox\Builder\Concerns\InteractsWithCustomFields;
use Moox\Builder\Services\BuilderValuesResolver;
use Moox\Builder\Services\CustomFieldsManager;

trait MergesCustomFields
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function mergeCustomFields(array $payload): array
    {
        $model = $this->resource;

        if (! in_array(InteractsWithCustomFields::class, class_uses_recursive($model), true)) {
            return $payload;
        }

        /** @var InteractsWithCustomFields $model */
        $manager = app(CustomFieldsManager::class);
        $entity = $model::resolveCustomFieldsEntity();

        $presented = app(BuilderValuesResolver::class)->present(
            $manager->fieldsForEntity($entity),
            $model->customFields(),
        );

        return array_merge($payload, $presented);
    }
}
