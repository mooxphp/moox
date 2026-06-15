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
        if ($entities === []) {
            return;
        }

        FieldValue::query()
            ->where('field_name', $fieldName)
            ->whereIn('entity', $entities)
            ->delete();
    }
}
