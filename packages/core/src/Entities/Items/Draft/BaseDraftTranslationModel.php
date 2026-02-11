<?php

namespace Moox\Core\Entities\Items\Draft;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property \Carbon\Carbon|null $to_publish_at
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon|null $to_unpublish_at
 * @property \Carbon\Carbon|null $unpublished_at
 * @property \Carbon\Carbon|null $restored_at
 * @property string|null $translation_status
 * @property int|null $published_by_id
 * @property string|null $published_by_type
 * @property int|null $unpublished_by_id
 * @property string|null $unpublished_by_type
 * @property int|null $deleted_by_id
 * @property string|null $deleted_by_type
 * @property int|null $restored_by_id
 * @property string|null $restored_by_type
 * @property int|null $created_by_id
 * @property string|null $created_by_type
 * @property int|null $updated_by_id
 * @property string|null $updated_by_type
 *
 * @method static \Illuminate\Database\Eloquent\Builder|static withTrashed(bool $withTrashed = true)
 */
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
            if (empty($model->translation_status)) {
                $model->translation_status = 'draft';
            }

            if (auth()->check()) {
                $model->createdBy()->associate(auth()->user());
            }
        });

        static::deleted(function ($model) {
            $model->deleted_by_id = auth()->user()->id;
            $model->deleted_by_type = auth()->user()->getMorphClass();
        });

        static::updating(function ($model) {
            $model->updatedBy()->associate(auth()->user());
        });

        static::saved(function ($model) {
            DB::afterCommit(function () use ($model) {
                $model->checkAndUpdateMainEntryStatus();
            });
        });
    }

    /**
     * Check and update main entry status based on translation statuses
     */
    protected function checkAndUpdateMainEntryStatus(): void
    {
        $mainEntry = $this->getMainEntry();

        if (! $mainEntry) {
            return;
        }

        $config = config('core.draft_publish_logic', [
            'auto_publish_single' => true,
            'prompt_when_all_published' => true,
            'prompt_when_any_published' => false,
        ]);

        $mainEntry->load('translations');
        $allTranslations = $mainEntry->translations;
        $translationCount = $allTranslations->count();

        $publishedCount = $allTranslations->where('translation_status', 'published')->count();

        if ($translationCount === 1 && $config['auto_publish_single']) {
            $singleTranslation = $allTranslations->first();
            $newStatus = null;

            if ($singleTranslation->translation_status === 'published') {
                $newStatus = 'published';
            } elseif (in_array($singleTranslation->translation_status, ['draft', 'waiting', 'private', 'scheduled'])) {
                $newStatus = $singleTranslation->translation_status;
            }

            if ($newStatus && $mainEntry->status !== $newStatus) {
                $mainEntry->status = $newStatus;
                $mainEntry->timestamps = false; // Prevent updated_at from changing
                $mainEntry->save();
                $mainEntry->timestamps = true;
            }
        }

        if ($translationCount > 1) {
            if ($publishedCount === 0 && $mainEntry->status === 'published') {
                $mainEntry->status = 'draft';
                $mainEntry->timestamps = false;
                $mainEntry->save();
                $mainEntry->timestamps = true;
            }

            if ($mainEntry->status === 'published' && $publishedCount < $translationCount) {
                $unpublishedStatuses = $allTranslations
                    ->where('translation_status', '!=', 'published')
                    ->pluck('translation_status')
                    ->countBy()
                    ->sortDesc();

                $newStatus = $unpublishedStatuses->keys()->first() ?? 'draft';
                if ($mainEntry->status !== $newStatus) {
                    $mainEntry->status = $newStatus;
                    $mainEntry->timestamps = false;
                    $mainEntry->save();
                    $mainEntry->timestamps = true;
                }
            }
        }
    }

    /**
     * Get the main entry (parent model) for this translation
     * Uses the translatable relation that already exists
     */
    protected function getMainEntry()
    {
        $tableName = $this->getTable();
        $foreignKey = str_replace('_translations', '_id', $tableName);

        if (isset($this->attributes[$foreignKey]) && $this->attributes[$foreignKey]) {
            $parentClass = get_class($this);
            $parentClass = str_replace('Translation', '', $parentClass);

            if (class_exists($parentClass)) {
                return $parentClass::find($this->attributes[$foreignKey]);
            }
        }

        return null;
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
