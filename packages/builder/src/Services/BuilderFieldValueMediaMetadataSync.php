<?php

declare(strict_types=1);

namespace Moox\Builder\Services;

use Illuminate\Support\Facades\Schema;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Media\Models\Media;

final class BuilderFieldValueMediaMetadataSync
{
    public function __construct(
        protected CustomFieldsManager $customFieldsManager,
        protected EntityRegistry $entityRegistry,
    ) {}

    public function syncForMedia(Media|int $media): void
    {
        if (! Schema::hasTable('builder_field_values')) {
            return;
        }

        $mediaId = $media instanceof Media ? (int) $media->getKey() : $media;
        $fresh = MediaFieldValueSupport::snapshotFromMediaId($mediaId);

        if ($fresh === null) {
            return;
        }

        FieldValue::query()
            ->whereNotNull('value_json')
            ->lazyById()
            ->each(function (FieldValue $row) use ($fresh, $mediaId): void {
                $ids = MediaFieldValueSupport::extractIds($row->value_json);

                if (! in_array($mediaId, $ids, true)) {
                    return;
                }

                $snapshot = MediaFieldValueSupport::normalizeSnapshot($row->value_json);

                if ($snapshot === null || (int) ($snapshot['id'] ?? 0) !== $mediaId) {
                    return;
                }

                $row->update(['value_json' => $fresh]);

                $this->customFieldsManager->forgetValuesCache($row->entity, $row->record_id);
                $this->flushCustomFieldsCacheForRow($row);
            });
    }

    protected function flushCustomFieldsCacheForRow(FieldValue $row): void
    {
        $modelClass = $this->entityRegistry->modelFor($row->entity);

        if ($modelClass === null) {
            return;
        }

        $model = $modelClass::query()->find($row->record_id);

        if ($model !== null && method_exists($model, 'flushCustomFieldsCache')) {
            $model->flushCustomFieldsCache();
        }
    }
}
