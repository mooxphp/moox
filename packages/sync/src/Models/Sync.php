<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Sync extends Model
{
    protected $fillable = [
        'title',
        'source_platform_id',
        'source_model',
        'target_platform_id',
        'target_model',
        'all_fields',
        'field_mappings',
        'has_errors',
        'last_sync',
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
