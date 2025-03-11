<?php

namespace Moox\Media\Models;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'file_name',
        'disk',
        'mime_type',
        'size',
        'custom_properties',
        'responsive_images',
        'model_id',
        'model_type',
        'collection_name',
        'title',
        'alt',
        'description',
        'internal_note',
        'original_model_id',
        'original_model_type',
    ];

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
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
