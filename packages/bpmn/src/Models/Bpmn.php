<?php

namespace Moox\Bpmn\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\Media;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bpmn extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasMediaUsable;

    protected $fillable = [
        'title',
        'description',
        'is_published',
        'status',
        'bpmn_media_id',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_published' => 'boolean',
            'bpmn_media_id' => 'integer',
        ];
    }


    /**
     * Media attached via the media_usables pivot.
     */
    public function mediaThroughUsables()
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }
}
