<?php

namespace Moox\Draft\Models;

use Moox\User\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class DraftTranslation extends BaseDraftTranslationModel
{
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        // Translation fields
        'locale',
        'draft_id',
        'title',
        'slug',
        'permalink',
        'translation_status',
        'description',
        'content',
        'author_id',
        'author_type',

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

    protected $casts = [
        // DateTime casts
        'published_at' => 'datetime',
        'to_publish_at' => 'datetime',
        'to_unpublish_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by_id = auth()->id();
                $model->created_by_type = auth()->user()::class;
            }

            if ($model->author_id && !$model->author_type) {
                $model->author_type = User::class;
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by_id = auth()->id();
                $model->updated_by_type = auth()->user()::class;
            }

            if ($model->author_id && !$model->author_type) {
                $model->author_type = User::class;
            }
        });
    }

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
                    $this->unpublished_by_id = auth()->id();
                    $this->unpublished_by_type = auth()->user()::class;
                }

                $this->published_at = null;
                $this->published_by_id = null;
                $this->published_by_type = null;
                break;

            case 'published':
                $this->published_at = now();
                $this->published_by_id = auth()->id();
                $this->published_by_type = auth()->user()::class;
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;
                $this->unpublished_at = null;
                $this->unpublished_by_id = null;
                $this->unpublished_by_type = null;
                break;

            case 'waiting':
                $this->published_at = null;
                $this->published_by_id = null;
                $this->published_by_type = null;
                $this->to_publish_at = null;
                $this->unpublished_at = null;
                $this->to_unpublish_at = null;
                break;

            case 'privat':
                if ($this->published_at !== null) {
                    $this->unpublished_at = now();
                    $this->unpublished_by_id = auth()->id();
                    $this->unpublished_by_type = auth()->user()::class;
                }

                $this->published_at = null;
                $this->published_by_id = null;
                $this->published_by_type = null;
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;
                break;

            case 'draft':
            default:
                if ($this->published_at !== null) {
                    $this->unpublished_at = now();
                    $this->unpublished_by_id = auth()->id();
                    $this->unpublished_by_type = auth()->user()::class;
                }

                $this->published_at = null;
                $this->published_by_id = null;
                $this->published_by_type = null;
                $this->to_publish_at = null;
                $this->to_unpublish_at = null;

                break;
        }
    }

}
