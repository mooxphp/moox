<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Sync extends Model
{
    protected $fillable = [
        'syncable_id',
        'syncable_type',
        'source_platform_id',
        'target_platform_id',
        'last_sync',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'last_sync' => 'datetime',
    ];

    public function sourcePlatform()
    {
        //return $this->belongsTo(Platform::class, 'source_platform_id');
    }

    public function targetPlatform()
    {
        //return $this->belongsTo(Platform::class, 'target_platform_id');
    }

    public function syncable()
    {
        return $this->morphTo();
    }
}
