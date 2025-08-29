<?php

namespace Moox\Core\Entities\Items\Draft;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class BaseDraftModel extends Model implements TranslatableContract
{
    use SoftDeletes, Translatable;

    public $timestamps = false;

    /**
     * Constructor to set up translated attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->translatedAttributes = array_merge(
            $this->getBaseTranslatedAttributes(),
            $this->getCustomTranslatedAttributes()
        );
    }

    /**
     * Get base translated attributes that should always be present
     */
    protected function getBaseTranslatedAttributes(): array
    {
        return [
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
            'created_at',

            // Updated by fields
            'updated_by_id',
            'updated_by_type',
            'updated_at',

            // Translation status
            'translation_status',
        ];
    }

    /**
     * Get custom translated attributes for child models to extend
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [];
    }

    /**
     * Translated attributes property - will be set by constructor
     */
    public $translatedAttributes = [];

    /**
     * Boot method for common draft functionality
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->ulid = (string) Str::ulid();
        });

        static::deleted(function ($model) {
            if ($model->isForceDeleting()) {
                $model->translations()->forceDelete();
            } else {
                $model->translations()->delete();
            }
        });

        static::restored(function ($model) {
            // Restore all trashed translations when main model is restored
            $model->translations()->withTrashed()->get()->each(function ($translation) {
                if ($translation->trashed()) {
                    $translation->restore();
                }
            });
        });
    }

    public function getUlidAttribute(): string
    {
        return $this->ulid ?? (string) Str::ulid();
    }

    public function getUuidAttribute(): string
    {
        return $this->uuid ?? (string) Str::uuid();
    }

    public function checkAndDeleteIfAllTranslationsDeleted(): void
    {
        if ($this->translations()->count() === 0) {
            $this->delete();
        }
    }

    /**
     * Publish the draft and set all necessary fields
     */
    public function publish(): void
    {
        $this->translation_status = 'published';
        $this->handleSchedulingDates();
    }

    /**
     * Unpublish the draft and set all necessary fields
     */
    public function unpublish(): void
    {
        $this->translation_status = 'draft';
        $this->handleSchedulingDates();
    }

    /**
     * Schedule the draft for publishing
     */
    public function scheduleForPublishing(?Carbon $publishAt = null): void
    {
        $this->translation_status = 'scheduled';
        if ($publishAt) {
            $this->to_publish_at = $publishAt;
        }
        $this->handleSchedulingDates();
    }

    /**
     * Set draft to waiting status (for review/approval)
     */
    public function setToWaiting(): void
    {
        $this->translation_status = 'waiting';
        $this->handleSchedulingDates();
    }

    /**
     * Set draft to private status (internal use only)
     */
    public function setToPrivate(): void
    {
        $this->translation_status = 'privat';
        $this->handleSchedulingDates();
    }

    /**
     * Publishing status accessors
     * These methods use the translation system to access the properties
     */
    public function isScheduledForPublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        $translation = $this->translate($locale);

        return $translation && $translation->to_publish_at !== null && $translation->published_at === null;
    }

    public function isPublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        $translation = $this->translate($locale);

        return $translation?->published_at !== null;
    }

    public function isScheduledForUnpublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        $translation = $this->translate($locale);

        return $translation && $translation->to_unpublish_at !== null && $translation->unpublished_at === null;
    }

    public function isUnpublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        $translation = $this->translate($locale);

        return $translation && $translation->unpublished_at !== null;
    }

    public function isRestored(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        $translation = $this->translate($locale);

        return $translation && $translation->restored_at !== null;
    }

    /**
     * Query scopes for publishing status
     */
    public function scopeScheduledForPublishing($query)
    {
        $locale = request()->query('lang') ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->whereNotNull('to_publish_at')
                ->whereNull('published_at');
        });
    }

    public function scopePublished($query)
    {
        $locale = request()->query('lang') ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->whereNotNull('published_at');
        });
    }

    public function scopeScheduledForUnpublishing($query)
    {
        $locale = request()->query('lang') ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->whereNotNull('to_unpublish_at')
                ->whereNull('unpublished_at');
        });
    }

    public function scopeUnpublished($query)
    {
        $locale = request()->query('lang') ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->whereNotNull('unpublished_at');
        });
    }

    public function scopeRestored($query)
    {
        $locale = request()->query('lang') ?? app()->getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale)
                ->whereNotNull('restored_at');
        });
    }

    /**
     * Translation helper methods
     */
    public function getAvailableTranslations(): array
    {
        return $this->translations->pluck('locale')->toArray();
    }

    public function hasTranslation(?string $locale = null): bool
    {
        if ($locale === null) {
            $locale = request()->query('lang') ?? app()->getLocale();
        }

        return $this->translations->contains('locale', $locale);
    }

    public function createTranslation(string $locale, array $attributes = []): void
    {
        $translation = $this->translateOrNew($locale);

        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->translatedAttributes)) {
                $translation->$key = $value;
            }
        }

        $this->translations()->save($translation);
    }

    public function deleteTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->delete();
    }

    /**
     * Override the setAttribute method to automatically handle translated attributes
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->translatedAttributes)) {
            $lang = request()->query('lang') ?? app()->getLocale();

            $this->translateOrNew($lang)->$key = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Override to get translated attributes
     */
    public function getAttribute($key)
    {
        if (in_array($key, $this->translatedAttributes)) {
            $lang = request()->query('lang') ?? app()->getLocale();

            return $this->translate($lang, false) ? $this->translate($lang, false)->$key : null;
        }

        return parent::getAttribute($key);
    }

    /**
     * Helper to get translated value
     */
    protected function getTranslated($key, $locale)
    {
        // First try to get from loaded translations
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations
                ->where('locale', $locale)
                ->first();

            if ($translation) {
                return $translation->$key;
            }
        }

        // Fallback to direct translation lookup
        $translation = $this->translate($locale);

        return $translation ? $translation->$key : '';
    }

    /**
     * Override toArray to include translations
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        if ($locale = request()->query('lang')) {
            foreach ($this->translatedAttributes as $attr) {
                $attributes[$attr] = $this->getTranslated($attr, $locale);
            }
        }

        return $attributes;
    }

    /**
     * Get the author (polymorphic relation)
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
