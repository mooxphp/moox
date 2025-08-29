<?php

namespace Moox\Core\Entities\Items\Draft;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseDraftTranslationModel extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    /**
     * Get the base fillable fields that should always be present
     */
    protected function getBaseFillable(): array
    {
        return [
            // Translation fields
            'locale',
            'translation_status',

            // Publishing schedule fields
            'to_publish_at',
            'published_at',
            'to_unpublish_at',
            'unpublished_at',

            // Actor fields
            'published_by_id',
            'published_by_type',
            'unpublished_by_id',
            'unpublished_by_type',

            // Soft delete and restoration fields
            'deleted_at',
            'deleted_by_id',
            'deleted_by_type',
            'restored_at',
            'restored_by_id',
            'restored_by_type',

            // Created by fields
            'created_by_id',
            'created_by_type',

            // Updated by fields
            'updated_by_id',
            'updated_by_type',
        ];
    }

    /**
     * Get the base casts that should always be present
     */
    protected function getBaseCasts(): array
    {
        return [
            // DateTime casts
            'to_publish_at' => 'datetime',
            'published_at' => 'datetime',
            'to_unpublish_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the fillable attributes by merging base and custom fillable
     */
    public function getFillable(): array
    {
        return array_merge($this->getBaseFillable(), $this->getCustomFillable());
    }

    /**
     * Get custom fillable for child models to extend
     */
    protected function getCustomFillable(): array
    {
        return [];
    }

    /**
     * Get the casts by merging base and custom casts
     */
    public function getCasts(): array
    {
        return array_merge($this->getBaseCasts(), $this->getCustomCasts());
    }

    /**
     * Get custom casts for child models to extend
     */
    protected function getCustomCasts(): array
    {
        return [];
    }

    /**
     * Boot method for common translation functionality
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
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

    /**
     * Handle translation status changes and scheduling dates
     */
    public function setTranslationStatusAttribute($value)
    {
        $oldValue = $this->getOriginal('translation_status') ?? $this->attributes['translation_status'] ?? null;

        $oldValue = $oldValue === '' ? null : $oldValue;
        $newValue = $value === '' ? null : $value;

        if (empty($newValue)) {
            return;
        }

        if ($oldValue === $newValue) {
            return;
        }

        $this->attributes['translation_status'] = $value;

        switch ($value) {
            case 'scheduled':
                if ($this->published_at !== null) {
                    $this->unpublished_at = now();
                    $this->unpublishedBy()->associate(auth()->user());
                }

                $this->published_at = null;
                $this->publishedBy()->dissociate();
                break;

            case 'published':
                $this->published_at = now();
                $this->publishedBy()->associate(auth()->user());
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;
                $this->unpublished_at = null;
                $this->unpublishedBy()->dissociate();
                break;

            case 'waiting':
                $this->published_at = null;
                $this->publishedBy()->dissociate();
                $this->to_publish_at = null;
                $this->unpublished_at = null;
                $this->to_unpublish_at = null;
                break;

            case 'privat':
                if ($this->published_at !== null) {
                    $this->unpublished_at = now();
                    $this->unpublishedBy()->associate(auth()->user());
                }

                $this->published_at = null;
                $this->publishedBy()->dissociate();
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;
                break;

            case 'draft':
            default:
                if ($this->published_at !== null) {
                    $this->unpublished_at = now();
                    $this->unpublishedBy()->associate(auth()->user());
                }

                $this->published_at = null;
                $this->publishedBy()->dissociate();
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;
                break;
        }
    }

    public static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }

    /**
     * Actor relationships for publishing
     */
    public function publishedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function unpublishedBy(): MorphTo
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
