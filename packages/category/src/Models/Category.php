<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Category\Database\Factories\CategoryFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Localization\Models\Localization;
use Moox\Media\Traits\HasMediaUsable;
use Override;
use Spatie\Image\Enums\Fit;
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
    use HasMediaUsable;
    use InteractsWithMedia;
    use NodeTrait;
    use SoftDeletes;

    public $incrementing = true;

    protected $keyType = 'int';

    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'slug',
            'permalink',
            'description',
            'content',
            'author_id',
            'author_type',
        ];
    }

    protected $fillable = [
        'is_active',
        'color',
        'weight',
        'count',
        'image',
        'parent_id',
        'basedata',
        'status',
        'uuid',
        'ulid',
        'custom_properties',
        'due_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
        'count' => 'integer',
        'basedata' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
        'custom_properties' => 'json',
    ];

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

    public function getDisplayTitleAttribute(): string
    {
        $defaultLocalization = Localization::where('is_default', true)->first();
        $mainLocale = $defaultLocalization?->locale_variant ?? config('app.locale', 'en');
        $currentLocale = request()->query('lang') ?? $mainLocale;

        if (method_exists($this, 'translate')) {
            $translation = $this->translate($currentLocale);
            if ($translation && ! empty($translation->title)) {
                return $translation->title;
            }

            $mainTranslation = $this->translate($mainLocale);
            if ($mainTranslation && ! empty($mainTranslation->title)) {
                return $mainTranslation->title.' ('.$mainLocale.')';
            }
        }

        return $this->attributes['title'] ?? ('ID: '.$this->id);
    }
}
