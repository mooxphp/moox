<?php

namespace Moox\Media\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Exception;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements HasMedia, TranslatableContract
{
    use InteractsWithMedia, Translatable;

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
        $this->addMediaConversion('preview')
            ->nonQueued()
            ->fit(Fit::Contain, 300, 300);

        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Contain, 150, 150);

        $this->addMediaConversion('medium')
            ->nonQueued()
            ->fit(Fit::Contain, 800, 600);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->fit(Fit::Contain, 1200, 900)
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
                $collection = MediaCollection::find($media->media_collection_id);
                $media->collection_name = $collection?->name ?? null;
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
