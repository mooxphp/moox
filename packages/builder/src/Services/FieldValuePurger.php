<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Moox\Builder\Models\FieldValue;

class FieldValuePurger
{
    public function purgeForRecord(string $entity, int|string $recordId): void
    {
        FieldValue::query()
            ->where('entity', $entity)
            ->where('record_id', $recordId)
            ->delete();
    }

    /**
     * @param  list<string>  $entities
     */
    public function purgeForFieldName(string $fieldName, array $entities): void
    {
        $this->purgeForFieldNames([$fieldName], $entities);
    }

    /**
     * @param  list<string>  $fieldNames
     * @param  list<string>  $entities
     */
    public function purgeForFieldNames(array $fieldNames, array $entities): void
    {
        if ($fieldNames === [] || $entities === []) {
            return;
        }

        FieldValue::query()
            ->whereIn('field_name', $fieldNames)
            ->whereIn('entity', $entities)
            ->delete();
    }
}
