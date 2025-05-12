<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Override;
use Kalnoy\Nestedset\NodeTrait;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Category\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Moox\Category\Models\Category> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Moox\Category\Models\Category> $ancestors
 * @property-read \Moox\Category\Models\Category|null $parent
 * @method static \Moox\Category\Database\Factories\CategoryFactory factory($count = null, $state = [])
 */

class Category extends BaseDraftModel implements HasMedia
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;
    use InteractsWithMedia;
    public $incrementing = false;
    protected $keyType = 'int';

    public $translatedAttributes = [
        'title',
        'status',
        'slug',
        'content',
        'data'
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

}
