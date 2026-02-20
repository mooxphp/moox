<?php

namespace Moox\Media\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

trait HasMediaUsable
{
    protected static function bootHasMediaUsable(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                MediaUsable::query()
                    ->where('media_usable_id', $model->getKey())
                    ->where('media_usable_type', get_class($model))
                    ->delete();

                return;
            }

            if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                MediaUsable::query()
                    ->where('media_usable_id', $model->getKey())
                    ->where('media_usable_type', get_class($model))
                    ->delete();
            }
        });
    }

    /**
     * Synchronize media metadata in model JSON fields when media is updated
     */
    public static function syncMediaMetadata(Media $media, ?string $oldFileName = null): void
    {
        $oldFileName = $oldFileName ?? $media->getOriginal('file_name') ?? $media->file_name;
        $newFileName = $media->file_name;

        // Get all translations from media_translations table
        $translations = DB::table('media_translations')
            ->where('media_id', $media->id)
            ->get()
            ->keyBy('locale');

        // Get all models that use this media
        $usables = DB::table('media_usables')
            ->where('media_id', $media->id)
            ->get();

        foreach ($usables as $usable) {
            $modelClass = $usable->media_usable_type;
            $model = $modelClass::find($usable->media_usable_id);

            if (! $model) {
                continue;
            }

            // Try to get en_US translation first (as requested)
            $translation = $translations->get('en_US');

            // Fallback to first available translation if en_US doesn't exist
            if (! $translation && $translations->isNotEmpty()) {
                $translation = $translations->first();
            }

            if (! $translation) {
                // No translations available, only update file_name
                $title = null;
                $alt = null;
                $description = null;
                $internalNote = null;
            } else {
                $title = $translation->title;
                $alt = $translation->alt;
                $description = $translation->description;
                $internalNote = $translation->internal_note;
            }

            // Check if field is JSON type
            $table = $model->getTable();
            $jsonFields = [];
            try {
                $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Type LIKE '%json%'");
                foreach ($columns as $column) {
                    $jsonFields[] = $column->Field;
                }
            } catch (\Exception $e) {
                // If we can't determine column types, assume all fields need json_encode
            }

            foreach ($model->getAttributes() as $field => $value) {
                // Skip if field doesn't contain JSON data
                if ($value === null) {
                    continue;
                }

                // Parse JSON value
                $jsonData = null;
                if (is_string($value)) {
                    $jsonData = json_decode($value, true);
                } elseif (is_array($value)) {
                    $jsonData = $value;
                }

                if (! is_array($jsonData)) {
                    continue;
                }

                $changed = false;
                $newData = null;

                // Handle single object (not array)
                if (isset($jsonData['file_name']) && $jsonData['file_name'] === $oldFileName) {
                    $newData = [
                        'file_name' => $newFileName,
                        'title' => $title,
                        'alt' => $alt,
                        'description' => $description,
                        'internal_note' => $internalNote,
                    ];
                    $changed = true;
                } else {
                    // Handle array of objects
                    foreach ($jsonData as $key => $item) {
                        if (is_array($item) && isset($item['file_name']) && $item['file_name'] === $oldFileName) {
                            $jsonData[$key] = [
                                'file_name' => $newFileName,
                                'title' => $title,
                                'alt' => $alt,
                                'description' => $description,
                                'internal_note' => $internalNote,
                            ];
                            $changed = true;
                            $newData = $jsonData;
                            break;
                        }
                    }
                }

                if ($changed) {
                    // If field is JSON type, assign array directly (Laravel will encode it)
                    // Otherwise, encode it manually
                    if (in_array($field, $jsonFields)) {
                        $model->{$field} = $newData ?? [
                            'file_name' => $newFileName,
                            'title' => $title,
                            'alt' => $alt,
                            'description' => $description,
                            'internal_note' => $internalNote,
                        ];
                    } else {
                        $model->{$field} = json_encode($newData ?? [
                            'file_name' => $newFileName,
                            'title' => $title,
                            'alt' => $alt,
                            'description' => $description,
                            'internal_note' => $internalNote,
                        ], JSON_UNESCAPED_UNICODE);
                    }
                    $model->save();
                }
            }
        }
    }
}
