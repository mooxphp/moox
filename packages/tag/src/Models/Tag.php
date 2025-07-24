<?php

declare(strict_types=1);

namespace Moox\Tag\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Moox\Tag\Database\Factories\TagFactory;
use Override;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Tag extends Model implements HasMedia, TranslatableContract
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Translatable;

    protected $table = 'tags';

    public $translatedAttributes = ['title', 'slug', 'content'];

    protected $fillable = [
        'color',
        'weight',
        'count',
        'featured_image_url',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
    ];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : 'active';
    }

    public function taggables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'taggable');
    }

    public function detachAllTaggables(): void
    {
        DB::table('taggables')->where('tag_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Tag $tag): void {
            $tag->detachAllTaggables();
        });
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->nonQueued()
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
