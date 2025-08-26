<?php

namespace Moox\Record\Models;

use Moox\User\Models\User;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Record\Enums\RecordStatus;

class Record extends BaseRecordModel
{
    protected $fillable = [
        'title',
        'content',
        'status',
        'slug',
        'permalink',
        'author_id',
        'created_by_id',
        'updated_by_id',
        'deleted_by_id',
        'restored_by_id',
        'restored_at',
        'custom_properties',
    ];

    protected $casts = [
        'title' => 'string',
        'status' => RecordStatus::class,
        'slug' => 'string',
        'permalink' => 'string',
        'author_id' => 'string',
        'created_by_id' => 'string',
        'updated_by_id' => 'string',
        'deleted_by_id' => 'string',
        'restored_by_id' => 'string',
        'restored_at' => 'datetime',
        'custom_properties' => 'json',
    ];

    public static function getResourceName(): string
    {
        return 'item';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

}
