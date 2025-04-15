<?php

namespace Moox\Draft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\HasScheduledPublish;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Draft extends BaseDraftModel implements HasMedia
{
    use HasModelTaxonomy, HasScheduledPublish, InteractsWithMedia;

    /**
     * Attributes that should be translated
     */
    public $translatedAttributes = [
        'title', 
        'slug', 
        'description', 
        'content', 
        'status',
        'author_id', 
        'to_publish_at', 
        'published_at', 
        'to_unpublish_at', 
        'unpublished_at', 
        'published_by_id', 
        'unpublished_by_id',
        'deleted_at',
        'deleted_by_id',
        'restored_at',
        'restored_by_id',
    ];

    protected $fillable = [
        'is_active',
        'data',
        'image',
        'type',
        'color',
        'due_at',
        'uuid',
        'ulid',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'data' => 'json',
        'image' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
            $model->ulid = (string) \Illuminate\Support\Str::ulid();
        });
        static::created(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Created', ['model' => $model]);
        });

        static::updated(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Updated', ['model' => $model]);
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Deleted', ['model' => $model]);
        });

        


        static::retrieved(function ($model) {
             $model->handleSchedulingDates();
        });

        static::saving(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Saving', ['model' => $model]);
        });

        static::saved(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Saved', ['model' => $model]);
        });

        static::deleting(function ($model) {
            \Illuminate\Support\Facades\Log::info('Draft Deleting', ['model' => $model]);
        });

        
     
        
    }

    public function getUlidAttribute(): string
    {
        return $this->ulid ?? (string) \Illuminate\Support\Str::ulid();
    }

    public function getUuidAttribute(): string
    {
        return $this->uuid ?? (string) \Illuminate\Support\Str::uuid();
    }

    public static function getResourceName(): string
    {
        return 'draft';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300);
    }

    public function mediaThroughUsables()
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }

    /**
     * Handle scheduling dates based on status changes
     */
    public function handleSchedulingDates(): void
    {
        
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);
        
        \Illuminate\Support\Facades\Log::info('Status: ' . $locale);
        \Illuminate\Support\Facades\Log::info('Status: ' . $translation);
        \Illuminate\Support\Facades\Log::info('Status: ' . $translation->status);
        if (!$translation) {
            return;
        }

        switch ($translation->status) {
            case 'scheduled':
                if (!$translation->to_publish_at) {
                    $translation->to_publish_at = now();
                }
                $translation->published_at = null;
                $translation->unpublished_at = null;
                break;

            case 'published':
                $translation->published_at = now();
                $translation->to_publish_at = null;
                $translation->unpublished_at = null;
                $translation->to_unpublish_at = null;
                break;

            case 'draft':
            default:
                $translation->published_at = null;
                $translation->to_publish_at = null;
                $translation->unpublished_at = null;
                $translation->to_unpublish_at = null;
                break;
        }

        $translation->save();


    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Relationships for actors
    public function publishedBy()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->morphTo();
    }

    public function unpublishedBy()
    {
        return $this->morphTo();
    }

    public function deletedBy()
    {
        return $this->morphTo();
    }

    public function restoredBy()
    {
        return $this->morphTo();
    }

    /**
     * Publishing status accessors
     * These methods now use the translation system to access the properties
     */
    public function isScheduledForPublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->to_publish_at !== null && $translation->published_at === null;
    }

    public function isPublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation?->published_at !== null;
    }

    public function isScheduledForUnpublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->to_unpublish_at !== null && $translation->unpublished_at === null;
    }

    public function isUnpublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->unpublished_at !== null;
    }

    /**
     * Query scopes
     * These scopes now work with the translation system
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

    /**
     * Restoration status
     */
    public function isRestored(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var \Moox\Draft\Models\DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->restored_at !== null;
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
     * Get all available translations for this model
     */
    public function getAvailableTranslations(): array
    {
        return $this->translations->pluck('locale')->toArray();
    }

    /**
     * Check if a translation exists for a specific locale
     */
    public function hasTranslation(?string $locale = null): bool
    {
        if ($locale === null) {
            $locale = request()->query('lang') ?? app()->getLocale();
        }

        return $this->translations->contains('locale', $locale);
    }

    /**
     * Create a new translation for a specific locale
     */
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

    /**
     * Delete a translation for a specific locale
     */
    public function deleteTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->delete();
    }
}
