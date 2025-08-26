<?php

namespace Moox\Core\Entities\Items\Record;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BaseRecordModel extends Model
{
    use SoftDeletes;
    /**
     * Boot method for common draft functionality
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->ulid = (string) Str::ulid();

            if (auth()->check()) {
                $model->created_by_id = auth()->id();
                $model->created_by_type = auth()->user()::class;
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by_id = auth()->id();
                $model->updated_by_type = auth()->user()::class;
            }
        });
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function deletedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function restoredBy(): MorphTo
    {
        return $this->morphTo();
    }
}
