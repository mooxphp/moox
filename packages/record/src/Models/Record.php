<?php

namespace Moox\Record\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Record\Enums\RecordStatus;

class Record extends BaseRecordModel
{
    use HasModelTaxonomy;

    protected $fillable = [
        'title',
        'content',
        'status',
        'slug',
        'permalink',
        'author_id',
        'author_type',
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
        return 'record';
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
