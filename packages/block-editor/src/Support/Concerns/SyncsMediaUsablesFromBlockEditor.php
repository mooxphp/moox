<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Support\Concerns;

use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\MediaUsable;

trait SyncsMediaUsablesFromBlockEditor
{
    public static function syncMediaUsablesFromContent(Model $record): void
    {
        static::syncMediaUsablesFromFields($record, static::blockEditorFields());
    }

    public static function syncMediaUsablesFromFields(Model $record, array $blockEditorFields): void
    {
        if (! $record->exists || $record->getKey() === null) {
            return;
        }

        $mediaIds = [];
        foreach ($blockEditorFields as $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }

            static::collectMediaIdsFromContentNode(
                static::normalizeBlockEditorContent($record->getAttribute($field)),
                $mediaIds
            );
        }

        $desiredMediaIds = array_values(array_unique(array_map('intval', $mediaIds)));
        sort($desiredMediaIds);

        $recordClass = get_class($record);
        $query = MediaUsable::query()
            ->where('media_usable_type', $recordClass)
            ->where('media_usable_id', $record->getKey());

        $existingMediaIds = $query
            ->pluck('media_id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $mediaIdsToAttach = array_values(array_diff($desiredMediaIds, $existingMediaIds));
        $mediaIdsToDetach = array_values(array_diff($existingMediaIds, $desiredMediaIds));

        if ($mediaIdsToDetach !== []) {
            MediaUsable::query()
                ->where('media_usable_type', $recordClass)
                ->where('media_usable_id', $record->getKey())
                ->whereIn('media_id', $mediaIdsToDetach)
                ->delete();
        }

        foreach ($mediaIdsToAttach as $mediaId) {
            MediaUsable::query()->create([
                'media_id' => $mediaId,
                'media_usable_type' => $recordClass,
                'media_usable_id' => $record->getKey(),
            ]);
        }
    }

    private static function collectMediaIdsFromContentNode(mixed $node, array &$mediaIds): void
    {
        if (! is_array($node)) {
            return;
        }

        $type = $node['type'] ?? null;
        if (
            is_string($type)
            && in_array($type, ['image', 'video'], true)
            && isset($node['media_usables'])
            && is_array($node['media_usables'])
        ) {
            foreach ($node['media_usables'] as $usable) {
                if (! is_array($usable)) {
                    continue;
                }

                $mediaId = $usable['media_id'] ?? null;
                $normalizedMediaId = is_numeric($mediaId) ? (int) $mediaId : 0;
                if ($normalizedMediaId > 0) {
                    $mediaIds[] = $normalizedMediaId;
                }
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                static::collectMediaIdsFromContentNode($value, $mediaIds);
            }
        }
    }

    private static function normalizeBlockEditorContent(mixed $content): mixed
    {
        if (! is_string($content)) {
            return $content;
        }

        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $content;
    }
}
