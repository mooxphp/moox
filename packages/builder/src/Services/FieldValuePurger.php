<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Media\Models\MediaUsable;

class FieldValuePurger
{
    public function __construct(
        protected BuilderMediaUsageSync $mediaUsageSync,
        protected EntityRegistry $entityRegistry,
    ) {
    }

    public function purgeForRecord(string $entity, int|string $recordId, ?Model $record = null): void
    {
        FieldValue::query()
            ->where('entity', $entity)
            ->where('record_id', $recordId)
            ->delete();

        if ($record !== null) {
            $this->mediaUsageSync->purgeForRecord($record);

            return;
        }

        $modelClass = $this->entityRegistry->modelFor($entity);

        if ($modelClass !== null && class_exists(MediaUsable::class)) {
            MediaUsable::query()
                ->where('media_usable_id', $recordId)
                ->where('media_usable_type', $modelClass)
                ->delete();
        }
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
