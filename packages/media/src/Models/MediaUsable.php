<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;

class MediaUsable extends Model
{
    protected $table = 'media_usables';

    protected $fillable = [
        'media_id', 
        'media_usable_id', 
        'media_usable_type'
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function mediaUsable()
    {
        return $this->morphTo();
    }
}
