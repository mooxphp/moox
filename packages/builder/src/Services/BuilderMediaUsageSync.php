<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Media\Models\MediaUsable;

final class BuilderMediaUsageSync
{
    /**
     * @var list<string>
     */
    private const MEDIA_FIELD_TYPES = ['image', 'gallery', 'file'];

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     */
    public function syncForRecord(string $entity, Model $record, Collection $fields): void
    {
        if (! $this->canSync()) {
            return;
        }

        $mediaIds = $this->collectMediaIds($entity, $record, $fields);

        $query = MediaUsable::query()
            ->where('media_usable_id', $record->getKey())
            ->where('media_usable_type', $record::class);

        if ($mediaIds === []) {
            $query->delete();

            return;
        }

        (clone $query)->whereNotIn('media_id', $mediaIds)->delete();

        foreach ($mediaIds as $mediaId) {
            MediaUsable::query()->firstOrCreate([
                'media_id' => $mediaId,
                'media_usable_id' => $record->getKey(),
                'media_usable_type' => $record::class,
            ]);
        }
    }

    public function purgeForRecord(Model $record): void
    {
        if (! $this->canSync()) {
            return;
        }

        MediaUsable::query()
            ->where('media_usable_id', $record->getKey())
            ->where('media_usable_type', $record::class)
            ->delete();
    }

    /**
     * @param  Collection<int, FieldDefinition>  $fields
     * @return list<int>
     */
    protected function collectMediaIds(string $entity, Model $record, Collection $fields): array
    {
        $mediaFieldNames = $fields
            ->filter(fn (FieldDefinition $field): bool => in_array($field->type, self::MEDIA_FIELD_TYPES, true))
            ->pluck('name')
            ->all();

        if ($mediaFieldNames === []) {
            return [];
        }

        $ids = [];

        $rows = FieldValue::query()
            ->forRecord($entity, $record->getKey())
            ->whereIn('field_name', $mediaFieldNames)
            ->get();

        foreach ($rows as $row) {
            $ids = array_merge($ids, MediaFieldValueSupport::extractIds($row->value_json));
        }

        return array_values(array_unique(array_filter($ids)));
    }

    protected function canSync(): bool
    {
        return class_exists(MediaUsable::class) && Schema::hasTable('media_usables');
    }
}
