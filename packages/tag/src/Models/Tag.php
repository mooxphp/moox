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
use Illuminate\Support\Str;

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
            if (!empty($data['title'])) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                
                // Ensure slug uniqueness per locale
                $slug = $this->generateUniqueSlug($slug, $locale);
    
                $this->translateOrNew($locale)->fill([
                    'title' => $data['title'],
                    'slug' => $slug,
                    'content' => $data['content'] ?? null,
                ]);
            }
        }
        
        return $this;
    }
    
    private function generateUniqueSlug(string $slug, string $locale): string
    {
        $originalSlug = $slug;
        $count = 1;
    
        while (TagTranslation::where('slug', $slug)->where('locale', $locale)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
    
        return $slug;
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
        $tag = new static();
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
