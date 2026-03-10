<?php

namespace Moox\Media\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Exception;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Moox\Localization\Models\Localization;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * @property int|null $id
 * @property int|null $media_collection_id
 * @property string|null $title
 * @property string|null $alt
 * @property object|null $uploader
 * @property int|string|null $uploader_id
 * @property string|null $uploader_type
 * @property int|string|null $original_model_id
 * @property string|null $original_model_type
 */
class Media extends BaseMedia implements HasMedia, TranslatableContract
{
    use HasMediaUsable, InteractsWithMedia, Translatable;

    public $translatedAttributes = ['name', 'title', 'alt', 'description', 'internal_note'];

    protected $fillable = [
        'file_name',
        'disk',
        'mime_type',
        'size',
        'custom_properties',
        'responsive_images',
        'model_id',
        'model_type',
        'collection_name',
        'media_collection_id',
        'original_model_id',
        'original_model_type',
        'write_protected',
        'uploader_id',
        'uploader_type',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): MorphTo
    {
        return $this->morphTo();
    }

    public function collection()
    {
        return $this->belongsTo(MediaCollection::class, 'media_collection_id');
    }

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->fit(Fit::Contain, 150, 150);

        $this->addMediaConversion('preview')
            ->nonQueued()
            ->width(300);

        $this->addMediaConversion('medium_large')
            ->nonQueued()
            ->width(768);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->width(1024)
            ->quality(80);

        $this->addMediaConversion('1536')
            ->nonQueued()
            ->width(1536)
            ->quality(80);

        $this->addMediaConversion('2048')
            ->nonQueued()
            ->width(2048)
            ->quality(80);
    }

    protected static function booted()
    {
        parent::boot();

        static::saving(function ($media) {
            if ($media->exists && $media->getOriginal('write_protected')) {
                throw new Exception('This media item is write-protected.');
            }
        });

        static::deleting(function ($media) {
            if ($media->getOriginal('write_protected')) {
                throw new Exception('Diese Datei ist schreibgeschützt und kann nicht gelöscht werden.');
            }
        });

        static::deleting(function (Media $media) {
            $usables = DB::table('media_usables')
                ->where('media_id', $media->id)
                ->get();

            foreach ($usables as $usable) {
                $modelClass = $usable->media_usable_type;
                $model = $modelClass::find($usable->media_usable_id);

                if (! $model) {
                    continue;
                }

                foreach ($model->getAttributes() as $field => $value) {
                    $jsonData = json_decode($value, true);

                    if (! is_array($jsonData)) {
                        continue;
                    }

                    if (isset($jsonData['file_name']) && $jsonData['file_name'] === $media->file_name) {
                        $model->{$field} = null;

                        continue;
                    }

                    $changed = false;
                    foreach ($jsonData as $key => $item) {
                        if (is_array($item) && isset($item['file_name']) && $item['file_name'] === $media->file_name) {
                            unset($jsonData[$key]);
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $jsonData = array_values($jsonData);
                        $model->{$field} = empty($jsonData) ? null : json_encode($jsonData);
                    }
                }

                $model->save();
            }
        });

        static::saving(function ($media) {
            if ($media->media_collection_id) {
                $collectionChanged = false;
                if ($media->exists) {
                    if ($media->isDirty('media_collection_id')) {
                        $originalCollectionId = $media->getOriginal('media_collection_id');
                        $newCollectionId = $media->media_collection_id;
                        $collectionChanged = $originalCollectionId != $newCollectionId;
                    }
                } else {
                    $collectionChanged = true;
                }

                if (! $media->exists || $collectionChanged || empty($media->collection_name)) {
                    $collection = MediaCollection::with('translations')->find($media->media_collection_id);

                    if ($collection) {
                        $defaultLocale = config('app.locale');

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

                        $translation = $collection->translations->firstWhere('locale', $defaultLocale);
                        $newCollectionName = null;

                        if ($translation && ! empty($translation->getAttribute('name'))) {
                            $newCollectionName = $translation->getAttribute('name');
                        } else {
                            if ($collection->translations->isNotEmpty()) {
                                $firstTranslation = $collection->translations->first();
                                $newCollectionName = $firstTranslation->getAttribute('name');
                            } else {
                                $newCollectionName = $collection->name ?? null;
                            }
                        }

                        if ($collectionChanged || ! empty($newCollectionName)) {
                            $media->collection_name = $newCollectionName;
                        }
                    } else {
                        $media->collection_name = null;
                    }
                }
            } elseif ($media->isDirty('media_collection_id')) {
                $media->collection_name = null;
            }
        });
    }

    public function getReadableMimeType(): string
    {
        $mimeMap = [
            'application/pdf' => 'PDF',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/msword' => 'DOC',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'video/mp4' => 'MP4',
            'audio/mpeg' => 'MP3',
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
            'image/webp' => 'WEBP',
            'image/svg+xml' => 'SVG',
        ];

        return $mimeMap[$this->mime_type] ?? strtoupper(str_replace('application/', '', $this->mime_type));
    }
}
