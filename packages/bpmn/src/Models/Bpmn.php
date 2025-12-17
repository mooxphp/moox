<?php

namespace Moox\Bpmn\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bpmn extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'is_published',
        'status',
        'bpmn_xml',
        'bpmn_svg',
        'bpmn_media',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_published' => 'boolean',
            'bpmn_media' => 'array',
        ];
    }

    /**
     * Media attached via the media_usables pivot.
     */
    public function mediaThroughUsables()
    {
        return $this->morphToMany(Media::class, 'media_usable')
            ->using(MediaUsable::class)
            ->withTimestamps();
    }
}
