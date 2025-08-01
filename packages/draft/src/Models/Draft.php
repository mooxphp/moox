<?php

namespace Moox\Draft\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\HasScheduledPublish;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\User\Models\User;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property bool $is_active
 * @property array $data
 * @property array $image
 * @property string $type
 * @property string[] $translatedAttributes
 * @property Carbon|null $due_at
 * @property string $uuid
 * @property string $ulid
 * @property-read string $title
 * @property-read string $slug
 * @property-read string $description
 * @property-read string $content
 * @property-read string $status
 * @property-read int $author_id
 * @property-read Carbon|null $to_publish_at
 * @property-read Carbon|null $published_at
 * @property-read Carbon|null $to_unpublish_at
 * @property-read Carbon|null $unpublished_at
 * @property-read int|null $published_by_id
 * @property-read int|null $unpublished_by_id
 * @property-read Carbon|null $deleted_at
 * @property-read int|null $deleted_by_id
 * @property-read Carbon|null $restored_at
 * @property-read int|null $restored_by_id
 * @property-read User|null $author
 * @property-read Model|null $publishedBy
 * @property-read Model|null $updatedBy
 * @property-read Model|null $createdBy
 * @property-read Model|null $unpublishedBy
 * @property-read Model|null $deletedBy
 * @property-read Model|null $restoredBy
 * @property-read Collection<int, Media> $media
 */
class Draft extends BaseDraftModel implements HasMedia
{
    use HasModelTaxonomy, HasScheduledPublish, InteractsWithMedia, SoftDeletes;

    public $timestamps = false;

    /**
     * Attributes that should be translated
     */
    public $translatedAttributes = [
        'title',
        'slug',
        'permalink',
        'description',
        'content',
        'author_id',
        'author_type',
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',
        'published_by_id',
        'unpublished_by_id',
        'unpublished_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
        'created_at',
        'created_by_id',
        'created_by_type',
        'updated_at',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $fillable = [
        'is_active',
        'data',
        'image',
        'type',
        'color',
        'due_at',
        'status',
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
            $model->translations()->restore();
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
        switch ($this->status) {
            case 'scheduled':
                if (! $this->to_publish_at) {
                    $this->to_publish_at = now();
                }
                $this->published_at = null;
                $this->unpublished_at = null;
                break;

            case 'published':
                $this->published_at = now();
                $this->to_publish_at = null;
                $this->unpublished_at = null;
                $this->to_unpublish_at = null;
                break;

            case 'draft':
            default:
                $this->published_at = null;
                $this->to_publish_at = null;
                $this->unpublished_at = null;
                $this->to_unpublish_at = null;
                break;
        }

        $this->save();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Relationships for actors
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

    /**
     * Publishing status accessors
     * These methods now use the translation system to access the properties
     */
    public function isScheduledForPublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->to_publish_at !== null && $translation->published_at === null;
    }

    public function isPublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation?->published_at !== null;
    }

    public function isScheduledForUnpublishing(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var DraftTranslation|null $translation */
        $translation = $this->translate($locale);

        return $translation && $translation->to_unpublish_at !== null && $translation->unpublished_at === null;
    }

    public function isUnpublished(): bool
    {
        $locale = request()->query('lang') ?? app()->getLocale();
        /** @var DraftTranslation|null $translation */
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
        /** @var DraftTranslation|null $translation */
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
