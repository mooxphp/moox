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
use Illuminate\Support\Str;
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

    /**
     * Handle filling translations from form data
     */
    public function fillTranslations(array $translations): self
    {
        foreach ($translations as $locale => $data) {
            if (! empty($data['title'])) {
                // Get the translation for this locale (or create a new one)
                $translation = $this->translateOrNew($locale);

                // Check if title has changed
                if ($translation->title !== $data['title']) {
                    $translation->title = $data['title'];
                }

                // Handle the slug only if it has changed
                $slug = $data['slug'] ?? Str::slug($data['title']);
                if ($translation->slug !== $slug) {
                    $slug = $this->generateUniqueSlug($slug, $locale);
                    $translation->slug = $slug;
                }

                // Handle content only if it has changed
                if ($translation->content !== ($data['content'] ?? null)) {
                    $translation->content = $data['content'] ?? null;
                }

                $translation->save();
            }
        }

        return $this;
    }

    public function generateUniqueSlug(string $slug, string $locale, int $counter = 0): string
    {
        // Append counter if needed
        $uniqueSlug = $counter > 0 ? "{$slug}-{$counter}" : $slug;

        // Check if the slug exists for this locale
        $exists = static::whereHas('translations', function ($query) use ($uniqueSlug, $locale) {
            $query->where('slug', $uniqueSlug)->where('locale', $locale);
        })->exists();

        // If exists, try again with an incremented counter
        return $exists ? $this->generateUniqueSlug($slug, $locale, $counter + 1) : $uniqueSlug;
    }

    /**
     * Get all translations as a formatted array
     */
    public function getTranslationsArray(): array
    {
        $translations = [];

        foreach ($this->translations as $translation) {
            $translations[$translation->locale] = [
                'title' => $translation->title,
                'slug' => $translation->slug,
                'content' => $translation->content,
            ];
        }

        return $translations;
    }

    /**
     * Create a new tag with translations
     */
    public static function createWithTranslations(array $attributes, array $translations): self
    {
        $tag = new static;
        $tag->fill($attributes);
        $tag->save();

        $tag->fillTranslations($translations)->save();

        return $tag;
    }

    /**
     * Update tag with translations
     */
    public function updateWithTranslations(array $attributes, array $translations): self
    {
        $this->fill($attributes);
        $this->fillTranslations($translations);
        $this->save();

        return $this;
    }

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
}
