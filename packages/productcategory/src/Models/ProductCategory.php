<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Media\Models\Media;
use Moox\Media\Traits\HasMediaUsable;
use Moox\ProductCategory\Database\Factories\ProductCategoryFactory;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\Publish\Models\HasPublish;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

/**
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rgt
 * @property bool $is_active
 * @property bool $is_canonical
 * @property int|null $canonical_product_category_id
 * @property int $sort_order
 * @property string|null $external_reference
 * @property string|null $external_info
 * @property string|null $external_status
 * @property string|null $note
 * @property array<string, mixed>|null $image
 * @property array<string, mixed>|null $data
 * @property string[] $translatedAttributes
 * @property-read string|null $title
 * @property-read string|null $slug
 * @property-read string|null $description
 * @property-read ProductCategory|null $parent
 * @property-read ProductCategory|null $canonicalCategory
 */
class ProductCategory extends Model implements HasMedia, TranslatableContract
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasPublish;

    /** @use HasFactory<ProductCategoryFactory> */
    use HasFactory;

    use HasIdentifier;
    use HasMediaUsable;
    use HasRelations;
    use InteractsWithMedia;
    use HasSoftDelete;
    use Translatable;

    protected $table = 'product_categories';

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'slug',
        'description',
    ];

    public bool $useTranslationFallback = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'is_canonical',
        'canonical_product_category_id',
        'lft',
        'rgt',
        'sort_order',
        'note',
        'image',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_canonical' => false,
        'lft' => 0,
        'rgt' => 0,
        'sort_order' => 0,
    ];

    public static function getResourceName(): string
    {
        return 'productcategory';
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $category->normalizeCanonical();
            $category->assertCanonical();
        });

        static::saved(function (self $category): void {
            if ($category->wasRecentlyCreated || $category->wasChanged(['parent_id', 'sort_order'])) {
                static::rebuildTree();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'lft' => 'integer',
            'rgt' => 'integer',
            'is_canonical' => 'boolean',
            'canonical_product_category_id' => 'integer',
            'sort_order' => 'integer',
            'image' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function canonicalCategory(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_product_category_id');
    }

    public function normalizeCanonical(): void
    {
        if ($this->canonical_product_category_id === 0) {
            $this->canonical_product_category_id = null;
        }

        if ($this->is_canonical !== true) {
            $this->is_canonical = false;
            $this->canonical_product_category_id = null;
        }
    }

    public function assertCanonical(): void
    {
        if ($this->is_canonical !== true) {
            return;
        }

        $targetId = $this->canonical_product_category_id;
        if ($targetId === null) {
            throw ValidationException::withMessages([
                'canonical_product_category_id' => __('productcategory::fields.canonical_category_required'),
            ]);
        }

        $key = $this->integerKey();
        if ($this->exists && $key !== null && $targetId === $key) {
            throw ValidationException::withMessages([
                'canonical_product_category_id' => __('productcategory::fields.canonical_category_loop'),
            ]);
        }

        if (! static::query()->whereKey($targetId)->exists()) {
            throw ValidationException::withMessages([
                'canonical_product_category_id' => __('productcategory::fields.canonical_category_missing'),
            ]);
        }

        $seen = $this->exists && $key !== null ? [$key] : [];
        $current = $targetId;

        while ($current !== null) {
            if (in_array($current, $seen, true)) {
                throw ValidationException::withMessages([
                    'canonical_product_category_id' => __('productcategory::fields.canonical_category_loop'),
                ]);
            }

            $seen[] = $current;
            $next = static::query()->whereKey($current)->value('canonical_product_category_id');
            $current = match (true) {
                is_int($next) => $next,
                default => null,
            };
        }
    }

    private function integerKey(): ?int
    {
        $key = $this->getKey();

        return is_int($key) ? $key : null;
    }

    /**
     * @return HasMany<ProductCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Writes lft and rgt from parent_id and sort_order.
     * The stored bounds are not copied from an external left/right pair.
     */
    public static function rebuildTree(): void
    {
        /** @var list<array{id: int|string, parent_id: int|string|null, sort_order: int}> $nodes */
        $nodes = static::query()
            ->get(['id', 'parent_id', 'sort_order'])
            ->map(fn (self $category): array => [
                'id' => $category->getKey(),
                'parent_id' => $category->parent_id,
                'sort_order' => $category->sort_order,
            ])
            ->values()
            ->all();
    
        foreach (self::bounds($nodes) as $id => $bounds) {
            static::query()->whereKey($id)->update($bounds);
        }
    }

    /**
     * @param  list<array{id: int|string, parent_id: int|string|null, sort_order: int}>  $nodes
     * @return array<int|string, array{lft: int, rgt: int}>
     */
    private static function bounds(array $nodes): array
    {
        $known = [];

        foreach ($nodes as $node) {
            $known[$node['id']] = true;
        }

        /** @var array<int|string, list<array{id: int|string, parent_id: int|string|null, sort_order: int}>> $children */
        $children = [];

        foreach ($nodes as $node) {
            $parent = $node['parent_id'];
            if ($parent === null || ! isset($known[$parent])) {
                $parent = 0;
            }

            $children[$parent][] = $node;
        }

        foreach ($children as &$siblings) {
            usort($siblings, function (array $left, array $right): int {
                $byOrder = $left['sort_order'] <=> $right['sort_order'];

                return $byOrder !== 0 ? $byOrder : strcmp((string) $left['id'], (string) $right['id']);
            });
        }
        unset($siblings);

        $counter = 1;
        $bounds = [];

        $walk = function (int|string $parent) use (&$walk, &$counter, &$bounds, $children): void {
            foreach ($children[$parent] ?? [] as $node) {
                $left = $counter++;
                $walk($node['id']);
                $bounds[$node['id']] = [
                    'lft' => $left,
                    'rgt' => $counter++,
                ];
            }
        };

        $walk(0);

        return $bounds;
    }

    /**
     * @param  Builder<ProductCategory>  $query
     * @return Builder<ProductCategory>
     */
    public function scopeWhereAncestorOf(Builder $query, self $category): Builder
    {
        return $query
            ->where('lft', '<', $category->lft)
            ->where('rgt', '>', $category->rgt)
            ->orderBy('lft');
    }

    /**
     * @param  Builder<ProductCategory>  $query
     * @return Builder<ProductCategory>
     */
    public function scopeWhereDescendantOf(Builder $query, self $category): Builder
    {
        return $query
            ->where('lft', '>', $category->lft)
            ->where('rgt', '<', $category->rgt)
            ->orderBy('lft');
    }

    public function registerMediaConversions(?SpatieMedia $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->nonQueued()
            ->fit(Fit::Contain, 300, 300);
    }

    /**
     * @return BelongsToMany<Media, $this>
     */
    public function mediaThroughUsables(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id',
        )->where('media_usables.media_usable_type', '=', static::class);
    }

    protected static function newFactory(): ProductCategoryFactory
    {
        return ProductCategoryFactory::new();
    }
}
