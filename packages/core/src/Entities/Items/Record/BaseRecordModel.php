<?php

namespace Moox\Core\Entities\Items\Record;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BaseRecordModel extends Model
{
    use SoftDeletes;

    /** @var array<string, bool> */
    private static array $columnExistsCache = [];

    /**
     * Boot method for common draft functionality
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model): void {
            if (static::modelHasColumn($model, 'uuid')) {
                $model->uuid ??= (string) Str::uuid();
            }

            if (static::modelHasColumn($model, 'ulid')) {
                $model->ulid ??= (string) Str::ulid();
            }

            if (auth()->check() && static::modelHasColumn($model, 'created_by_id')) {
                $model->createdBy()->associate(auth()->user());
            }
        });

        static::updating(function (Model $model): void {
            if (auth()->check() && static::modelHasColumn($model, 'updated_by_id')) {
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

    protected static function modelHasColumn(Model $model, string $column): bool
    {
        $cacheKey = $model->getConnectionName().'|'.$model->getTable().'|'.$column;

        if (array_key_exists($cacheKey, static::$columnExistsCache)) {
            return static::$columnExistsCache[$cacheKey];
        }

        return static::$columnExistsCache[$cacheKey] = $model->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($model->getTable(), $column);
    }
}
