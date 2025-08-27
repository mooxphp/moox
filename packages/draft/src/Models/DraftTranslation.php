<?php

namespace Moox\Draft\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Draft\Enums\TranslationStatus;
use Moox\User\Models\User;

class DraftTranslation extends BaseDraftTranslationModel
{
    protected $casts = [
        'translation_status' => TranslationStatus::class,
    ];

    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'draft_id',
            'title',
            'slug',
            'permalink',
            'description',
            'content',
            'author_id',
            'author_type',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });

        static::updating(function ($model) {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });
    }
}
