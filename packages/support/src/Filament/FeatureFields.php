<?php

declare(strict_types=1);

namespace Moox\Support\Filament;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Lang;

final class FeatureFields
{
    /**
     * Shared field keys and the feature that owns their default translation.
     *
     * @var array<string, string>
     */
    private const FEATURES = [
        'active' => 'active',
        'is_active' => 'active',
        'archive' => 'archive',
        'archived_at' => 'archive',
        'archived_by_id' => 'archive',
        'archived_by_type' => 'archive',
        'audit' => 'audit',
        'created_at' => 'audit',
        'created_by_id' => 'audit',
        'created_by_type' => 'audit',
        'updated_at' => 'audit',
        'updated_by_id' => 'audit',
        'updated_by_type' => 'audit',
        'data' => 'data',
        'data_invalid' => 'data',
        'external' => 'external',
        'external_reference' => 'external',
        'external_info' => 'external',
        'external_status' => 'external',
        'external_status_undefined' => 'external',
        'identity' => 'identity',
        'id' => 'identity',
        'ulid' => 'identity',
        'uuid' => 'identity',
        'publish' => 'publish',
        'published_at' => 'publish',
        'published_by_id' => 'publish',
        'published_by_type' => 'publish',
        'unpublished_at' => 'publish',
        'unpublished_by_id' => 'publish',
        'unpublished_by_type' => 'publish',
        'relation' => 'relation',
        'soft_delete' => 'soft_delete',
        'deleted_at' => 'soft_delete',
        'deleted_by_id' => 'soft_delete',
        'deleted_by_type' => 'soft_delete',
        'restored_at' => 'soft_delete',
        'restored_by_id' => 'soft_delete',
        'restored_by_type' => 'soft_delete',
        'titles' => 'title',
        'translations' => 'title',
        'locale' => 'title',
        'title' => 'title',
        'slug' => 'title',
        'description' => 'title',
    ];

    /**
     * Entity translation wins. Otherwise the support default for that feature layer.
     *
     * @param  'forms'|'columns'|'models'  $layer
     */
    public static function label(string $translation, string $key, ?string $feature = null, string $layer = 'forms'): string
    {
        $entityKey = $translation.'.'.$key;

        if (self::defined($entityKey)) {
            return __($entityKey);
        }

        $feature ??= self::FEATURES[$key] ?? null;

        if (! is_string($feature) || $feature === '') {
            return __($entityKey);
        }

        foreach (array_unique([$layer, 'forms']) as $candidate) {
            $fallback = 'support::'.$feature.'.'.$candidate.'.'.$key;

            if (self::defined($fallback)) {
                return __($fallback);
            }
        }

        return __($entityKey);
    }

    public static function locked(string $translation, string $column): TextInput
    {
        return TextInput::make($column)
            ->label(self::label($translation, $column))
            ->disabled()
            ->dehydrated(false);
    }

    private static function defined(string $key): bool
    {
        return Lang::has($key);
    }
}
