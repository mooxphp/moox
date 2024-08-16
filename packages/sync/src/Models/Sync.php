<?php

namespace Moox\Sync\Models;

use Illuminate\Database\Eloquent\Model;

class Sync extends Model
{
    protected $fillable = [
        'status',
        'title',
        'source_platform_id',
        'source_model',
        'target_platform_id',
        'target_model',
        'use_platform_relations',
        'if_exists',
        'sync_ids',
        'sync_all_fields',
        'field_mappings',
        'use_transformer_class',
        'has_errors',
        'error_message',
        'interval', // in minutes
        'last_sync',
        'has_errors',
        'field_mappings',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'last_sync' => 'datetime',
        'sync_ids' => 'array',
        'field_mappings' => 'array',
        'status' => 'boolean',
        'sync_all_fields' => 'boolean',
        'use_platform_relations' => 'boolean',
        'has_errors' => 'boolean',
        'interval' => 'integer',
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
