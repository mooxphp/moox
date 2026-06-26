<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Support\Scopes\ScopeValue;
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

        if (self::isIndexedGallery($value)) {
            $ids = [];

            foreach ($value as $item) {
                if (is_array($item)) {
                    $ids = array_merge($ids, self::extractIds($item));
                }
            }

            return array_values(array_unique($ids));
        }

        return [];
    }

    public static function isIndexedGallery(mixed $value): bool
    {
        if (! is_array($value) || $value === [] || array_is_list($value)) {
            return false;
        }

        if (isset($value['id']) && is_numeric($value['id'])) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_array($item) || ! isset($item['id']) || ! is_numeric($item['id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{id: int, file_name: string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}>|null
     */
    public static function normalizeGallery(mixed $raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $raw = $decoded;
            }
        }

        if (! is_array($raw)) {
            return null;
        }

        if (self::isIndexedGallery($raw)) {
            $normalized = [];
            $index = 1;

            foreach ($raw as $item) {
                $snapshot = self::normalizeSnapshot($item);

                if ($snapshot !== null) {
                    $normalized[(string) $index] = $snapshot;
                    $index++;
                }
            }

            return $normalized === [] ? null : $normalized;
        }

        if (array_is_list($raw)) {
            return self::persistGallery($raw);
        }

        return null;
    }

    /**
     * @return array<string, array{id: int, file_name: string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}>|null
     */
    public static function persistGallery(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $ids = self::extractIds($value);

        if ($ids === []) {
            return null;
        }

        $attachments = [];
        $index = 1;

        foreach ($ids as $mediaId) {
            $snapshot = self::snapshotFromMediaId($mediaId);

            if ($snapshot !== null) {
                $attachments[(string) $index] = $snapshot;
                $index++;
            }
        }

        return $attachments === [] ? null : $attachments;
    }

    /**
     * @return list<int>
     */
    public static function normalizeGalleryForForm(mixed $stored): array
    {
        return self::extractIds($stored);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function presentGallery(mixed $value): array
    {
        $gallery = self::normalizeGallery($value);

        if ($gallery === null) {
            return [];
        }

        $presented = [];

        foreach ($gallery as $key => $snapshot) {
            $item = self::presentSingle($snapshot);

            if ($item !== null) {
                $presented[(string) $key] = $item;
            }
        }

        return $presented;
    }

    /**
     * @param  array{id: int, file_name: string, title: ?string, alt: ?string, description: ?string, internal_note: ?string}  $fresh
     */
    public static function replaceSnapshotInStoredValue(mixed $stored, int $mediaId, array $fresh): mixed
    {
        if (self::isIndexedGallery($stored)) {
            $updated = false;
            $gallery = [];

            foreach ($stored as $key => $item) {
                if (is_array($item) && (int) ($item['id'] ?? 0) === $mediaId) {
                    $gallery[$key] = $fresh;
                    $updated = true;
                } else {
                    $gallery[$key] = $item;
                }
            }

            return $updated ? $gallery : $stored;
        }

        $snapshot = self::normalizeSnapshot($stored);

        if ($snapshot !== null && $snapshot['id'] === $mediaId) {
            return $fresh;
        }

        return $stored;
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

    public static function resolveExpectedMediaScope(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        if (method_exists($record, 'deriveChildScope')) {
            $scope = $record->deriveChildScope('media');

            if (filled($scope)) {
                return self::normalizeScope($scope);
            }
        }

        if (method_exists($record, 'deriveScopeForOrigin')) {
            $scope = $record->deriveScopeForOrigin('media');

            if (filled($scope)) {
                return self::normalizeScope($scope);
            }
        }

        if (! class_exists(ScopeValue::class)) {
            return null;
        }

        return ScopeValue::forOriginString(
            $record->getAttribute('scope'),
            'media',
        );
    }

    /**
     * @return array{valid: bool, reason: ?string}
     */
    public static function mediaValidationResult(int $mediaId, string $fieldType, ?Model $record = null): array
    {
        if (! self::mediaExists($mediaId)) {
            return ['valid' => false, 'reason' => 'missing'];
        }

        $row = DB::table('media')->where('id', $mediaId)->first();

        if ($row === null) {
            return ['valid' => false, 'reason' => 'missing'];
        }

        if (in_array($fieldType, ['image', 'gallery'], true) && ! self::mediaRowIsImage($row)) {
            return ['valid' => false, 'reason' => 'invalid_type'];
        }

        $expectedScope = self::resolveExpectedMediaScope($record);

        if (filled($expectedScope) && Schema::hasColumn('media', 'scope')) {
            $mediaScope = self::normalizeScope($row->scope ?? null);

            if ($mediaScope !== $expectedScope) {
                return ['valid' => false, 'reason' => 'scope_mismatch'];
            }
        }

        return ['valid' => true, 'reason' => null];
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
            return [
                'id' => $snapshot['id'],
                'file_name' => $snapshot['file_name'],
                'title' => $snapshot['title'] ?? null,
                'alt' => $snapshot['alt'] ?? null,
                'url' => null,
                'thumbnail_url' => null,
                'preview_url' => null,
            ];
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

    protected static function mediaRowIsImage(object $row): bool
    {
        if (! Schema::hasColumn('media', 'mime_type')) {
            return true;
        }

        $mimeType = strtolower((string) ($row->mime_type ?? ''));

        return $mimeType !== '' && str_starts_with($mimeType, 'image/');
    }

    protected static function normalizeScope(mixed $scope): ?string
    {
        if (! is_string($scope) || trim($scope) === '') {
            return null;
        }

        if (! class_exists(ScopeValue::class)) {
            return trim($scope);
        }

        return ScopeValue::toStringOrNull($scope);
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
