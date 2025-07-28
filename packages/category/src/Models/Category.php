<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Category\Database\Factories\CategoryFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $title
 * @property string $status
 * @property string $slug
 * @property string $content
 * @property int $_lft
 * @property int $_rgt
 * @property string|null $color
 * @property int|null $weight
 * @property int|null $count
 * @property string|null $featured_image_url
 * @property int|null $parent_id
 * @property array|null $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, \Moox\Category\Models\Category> $children
 * @property-read Collection<int, \Moox\Category\Models\Category> $ancestors
 * @property-read \Moox\Category\Models\Category|null $parent
 *
 * @method static CategoryFactory factory($count = null, $state = [])
 */
class Category extends BaseDraftModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use NodeTrait;

    public $incrementing = true;

    public $timestamps = false;

    protected $keyType = 'int';

    public $translatedAttributes = [
        'title',
        'status',
        'slug',
        'content',
        'author_id',
        'author_type',
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',
        'published_by_id',
        'unpublished_by_id',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $fillable = [
        'color',
        'weight',
        'count',
        'featured_image_url',
        'parent_id',
        'basedata',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
        'basedata' => 'json',
    ];

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : 'active';
    }

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categorizables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'categorizable');
    }

    public function detachAllCategorizables(): void
    {
        DB::table('categorizables')->where('category_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            $category->detachAllCategorizables();
        });
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
