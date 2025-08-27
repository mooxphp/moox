<?php

namespace Moox\Core\Entities\Items\Record;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
                $model->createdBy()->associate(auth()->user());
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updatedBy()->associate(auth()->user());
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
