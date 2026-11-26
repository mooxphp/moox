<?php

declare(strict_types=1);

namespace Moox\Support\Audit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Moox\Support\Models\MergesColumns;

trait HasAudit
{
    use MergesColumns;

    public function initializeHasAudit(): void
    {
        $this->mergeColumns(
            [
                'created_by_id',
                'created_by_type',
                'updated_by_id',
                'updated_by_type',
            ],
            [
                'created_by_id' => 'integer',
                'created_by_type' => 'string',
                'updated_by_id' => 'integer',
                'updated_by_type' => 'string',
            ],
        );
    }

    protected static function bootHasAudit(): void
    {
        static::creating(static function (Model $model): void {
            $user = Auth::user();

            if ($user === null) {
                return;
            }

            if ($model->getAttribute('created_by_id') === null) {
                $model->setAttribute(
                    'created_by_id',
                    $user->getAuthIdentifier()
                );
                $model->setAttribute('created_by_type', $user->getMorphClass());
            }

            if ($model->getAttribute('updated_by_id') === null) {
                $model->setAttribute(
                    'updated_by_id',
                    $model->getAttribute('created_by_id')
                );
                $model->setAttribute(
                    'updated_by_type',
                    $model->getAttribute('created_by_type')
                );
            }
        });

        static::updating(static function (Model $model): void {
            foreach ([
                'created_at',
                'created_by_id',
                'created_by_type',
            ] as $attribute) {
                if ($model->isDirty($attribute)) {
                    $model->setAttribute(
                        $attribute,
                        $model->getOriginal($attribute)
                    );
                }
            }

            $user = Auth::user();

            if ($user === null) {
                return;
            }

            $model->setAttribute(
                'updated_by_id',
                $user->getAuthIdentifier()
            );
            $model->setAttribute(
                'updated_by_type',
                $user->getMorphClass()
            );
        });
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo(
            'createdBy',
            'created_by_type',
            'created_by_id'
        );
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo(
            'updatedBy',
            'updated_by_type',
            'updated_by_id'
        );
    }

    public function scopeCreatedBy(Builder $query, Model $user): Builder
    {
        return $query
            ->where('created_by_id', $user->getAuthIdentifier())
            ->where('created_by_type', $user->getMorphClass());
    }

    public function scopeUpdatedBy(Builder $query, Model $user): Builder
    {
        return $query
            ->where('updated_by_id', $user->getAuthIdentifier())
            ->where('updated_by_type', $user->getMorphClass());
    }
}