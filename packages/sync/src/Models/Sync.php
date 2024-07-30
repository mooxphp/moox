<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Sync extends Model
{
    protected $fillable = [
        'title',
        'model',
        'source_platform_id',
        'target_platform_id',
        'last_sync',
        'has_errors',
        'field_mappings',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'last_sync' => 'datetime',
        'field_mappings' => 'array',
    ];

    public function sourcePlatform()
    {
        return $this->belongsTo(Platform::class, 'source_platform_id');
    }

    public function targetPlatform()
    {
        return $this->belongsTo(Platform::class, 'target_platform_id');
    }
}
