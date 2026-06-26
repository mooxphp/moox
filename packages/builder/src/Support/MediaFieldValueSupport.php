<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;
use Moox\Media\Http\Resources\MediaItemResource;
use Moox\Media\Models\Media;

final class MediaFieldValueSupport
{
    /**
     * @return array{id: int, file_name: string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}|null
     */
    public static function normalizeSnapshot(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        if (! is_array($value)) {
            if (is_numeric($value)) {
                return self::snapshotFromMediaId((int) $value);
            }

            return null;
        }

        if (array_is_list($value) && isset($value[0]) && is_numeric($value[0]) && ! is_array($value[0])) {
            $id = (int) $value[0];

            return $id > 0 ? self::snapshotFromMediaId($id) : null;
        }

        if (! isset($value['id']) || ! is_numeric($value['id'])) {
            return null;
        }

        $id = (int) $value['id'];

        if ($id <= 0) {
            return null;
        }

        return [
            'id' => $id,
            'file_name' => (string) ($value['file_name'] ?? ''),
            'title' => self::nullableString($value['title'] ?? null),
            'alt' => self::nullableString($value['alt'] ?? null),
            'description' => self::nullableString($value['description'] ?? null),
            'internal_note' => self::nullableString($value['internal_note'] ?? null),
        ];
    }

    /**
     * @return list<int>
     */
    public static function extractIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return $id > 0 ? [$id] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        if (isset($value['id']) && is_numeric($value['id'])) {
            $id = (int) $value['id'];

            return $id > 0 ? [$id] : [];
        }

        if (array_is_list($value)) {
            return array_values(array_filter(array_map(
                static fn (mixed $id): ?int => is_numeric($id) ? (int) $id : null,
                $value,
            ), static fn (?int $id): bool => $id !== null && $id > 0));
        }

        return [];
    }

    public static function persistSingle(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $snapshot = self::normalizeSnapshot($value);

        if ($snapshot === null) {
            return null;
        }

        return self::snapshotFromMediaId($snapshot['id']);
    }

    public static function mediaExists(int $mediaId): bool
    {
        if ($mediaId <= 0 || ! self::canQueryMedia()) {
            return false;
        }

        return DB::table('media')->where('id', $mediaId)->exists();
    }

    public static function presentSingle(mixed $value): ?array
    {
        $snapshot = self::normalizeSnapshot($value);

        if ($snapshot === null) {
            return null;
        }

        if (! self::canQueryMedia()) {
            return $snapshot;
        }

        $media = Media::query()->find($snapshot['id']);

        if (! $media instanceof Media) {
            return array_merge($snapshot, [
                'url' => null,
                'thumbnail_url' => null,
                'preview_url' => null,
            ]);
        }

        return (new MediaItemResource($media))->resolve();
    }

    /**
     * @return array{id: int, file_name: string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}|null
     */
    public static function snapshotFromMediaId(int $mediaId): ?array
    {
        if ($mediaId <= 0 || ! self::canQueryMedia()) {
            return null;
        }

        $row = DB::table('media')->where('id', $mediaId)->first();

        if ($row === null) {
            return null;
        }

        $metadata = self::metadataFromTranslations($mediaId);

        return [
            'id' => $mediaId,
            'file_name' => (string) $row->file_name,
            'title' => $metadata['title'],
            'alt' => $metadata['alt'],
            'description' => $metadata['description'],
            'internal_note' => $metadata['internal_note'],
        ];
    }

    /**
     * @return array{title: ?string, alt: ?string, description: ?string, internal_note: ?string}
     */
    protected static function metadataFromTranslations(int $mediaId): array
    {
        if (! Schema::hasTable('media_translations')) {
            return [
                'title' => null,
                'alt' => null,
                'description' => null,
                'internal_note' => null,
            ];
        }

        $defaultLocale = 'en_US';

        if (class_exists(Localization::class)) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->with('language')
                ->first();

            if ($localization) {
                $defaultLocale = $localization->getAttribute('locale_variant') ?: $localization->language->alpha2;
            }
        }

        $translations = DB::table('media_translations')
            ->where('media_id', $mediaId)
            ->get()
            ->keyBy('locale');

        $translation = $translations->get($defaultLocale)
            ?? $translations->get('en_US')
            ?? $translations->first();

        if (! $translation) {
            return [
                'title' => null,
                'alt' => null,
                'description' => null,
                'internal_note' => null,
            ];
        }

        return [
            'title' => self::nullableString($translation->title ?? null),
            'alt' => self::nullableString($translation->alt ?? null),
            'description' => self::nullableString($translation->description ?? null),
            'internal_note' => self::nullableString($translation->internal_note ?? null),
        ];
    }

    protected static function canQueryMedia(): bool
    {
        return class_exists(Media::class) && Schema::hasTable('media');
    }

    protected static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
