<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Moox\Core\Traits\InteractsWithCustomFields;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Media\Models\Media;
use Moox\Media\Traits\HasMediaUsable;
use Moox\Product\Database\Factories\ProductFactory;
use Moox\Product\Enums\ProductType;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * @property int|null $product_category_id
 * @property int|null $parent_id
 * @property int|null $manufacturer_id
 * @property ProductType|null $type
 * @property string|null $sku
 * @property bool $is_orderable
 * @property bool|null $is_canonical
 * @property int|null $canonical_product_id
 * @property bool $is_active
 * @property int $sort_order
 * @property string|null $external_reference
 * @property string|null $external_info
 * @property string|null $external_status
 * @property string|null $note
 * @property array<int, array<string, mixed>>|null $image
 * @property array<string, mixed>|null $data
 * @property string[] $translatedAttributes
 * @property-read string|null $title
 * @property-read string|null $slug
 * @property-read string|null $description
 * @property-read Product|null $canonicalProduct
 */
class Product extends Model implements HasMedia, TranslatableContract
{
    use HasAudit;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasIdentifier;
    use HasMediaUsable;
    use HasRelations;
    use InteractsWithCustomFields, Translatable {
        Translatable::getAttribute insteadof InteractsWithCustomFields;
        Translatable::setAttribute insteadof InteractsWithCustomFields;
    }
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'products';

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
        'product_category_id',
        'parent_id',
        'manufacturer_id',
        'type',
        'sku',
        'is_orderable',
        'is_canonical',
        'canonical_product_id',
        'is_active',
        'sort_order',
        'external_reference',
        'external_info',
        'external_status',
        'note',
        'image',
        'data',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'archived_at',
        'archived_by_id',
        'archived_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
        'published_at',
        'published_by_id',
        'published_by_type',
        'unpublished_at',
        'unpublished_by_id',
        'unpublished_by_type',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_orderable' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    public static function getResourceName(): string
    {
        return 'product';
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->normalizeTypeFields();
            $product->assertTypeRules();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_category_id' => 'integer',
            'parent_id' => 'integer',
            'manufacturer_id' => 'integer',
            'type' => ProductType::class,
            'is_orderable' => 'boolean',
            'is_canonical' => 'boolean',
            'canonical_product_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'image' => 'array',
            'data' => 'array',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'archived_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'published_by_id' => 'integer',
            'unpublished_by_id' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function canonicalProduct(): BelongsTo
    {
        return $this->belongsTo(self::class, 'canonical_product_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
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

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function normalizeTypeFields(): void
    {
        if ($this->sku === '') {
            $this->sku = null;
        }

        foreach (['product_category_id', 'parent_id', 'canonical_product_id'] as $attribute) {
            if ($this->getAttribute($attribute) === 0 || $this->getAttribute($attribute) === '') {
                $this->setAttribute($attribute, null);
            }
        }

        if ($this->manufacturer_id === 0) {
            $this->manufacturer_id = null;
        }

        if ($this->type !== ProductType::Simple) {
            $this->sku = null;
            $this->is_orderable = false;
        }

        if ($this->type !== ProductType::Grouped && $this->type !== ProductType::Simple) {
            $this->is_canonical = null;
            $this->canonical_product_id = null;

            return;
        }

        if ($this->is_canonical !== true) {
            $this->canonical_product_id = null;
        }
    }

    public function assertTypeRules(): void
    {
        $messages = [];

        if (! $this->type instanceof ProductType) {
            $messages['type'] = __('product::fields.type_required');
        }

        if ($this->type === ProductType::Simple && $this->is_canonical !== true && ($this->sku === null || $this->sku === '')) {
            $messages['sku'] = __('product::fields.sku_required');
        }

        if ($this->sku !== null && $this->skuTaken()) {
            $messages['sku'] = __('product::fields.sku_taken');
        }

        if (($this->type === ProductType::Grouped || $this->type === ProductType::Simple) && $this->is_canonical === true && $this->canonical_product_id === null) {
            $messages['canonical_product_id'] = __('product::fields.canonical_product_required');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        if (($this->type === ProductType::Grouped || $this->type === ProductType::Simple) && $this->is_canonical === true) {
            $this->assertCanonicalTarget();
        }
    }

    public function skuTaken(): bool
    {
        if ($this->sku === null || $this->sku === '') {
            return false;
        }

        return static::query()
            ->where('sku', $this->sku)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    public function assertCanonicalTarget(): void
    {
        $targetId = $this->canonical_product_id;

        if ($targetId === null) {
            throw ValidationException::withMessages([
                'canonical_product_id' => __('product::fields.canonical_product_required'),
            ]);
        }

        $key = $this->integerKey();

        if ($this->exists && $key !== null && $targetId === $key) {
            throw ValidationException::withMessages([
                'canonical_product_id' => __('product::fields.canonical_product_loop'),
            ]);
        }

        if (! static::query()->whereKey($targetId)->exists()) {
            throw ValidationException::withMessages([
                'canonical_product_id' => __('product::fields.canonical_product_missing'),
            ]);
        }

        $seen = $this->exists && $key !== null ? [$key] : [];
        $current = $targetId;

        while ($current !== null) {
            if (in_array($current, $seen, true)) {
                throw ValidationException::withMessages([
                    'canonical_product_id' => __('product::fields.canonical_product_loop'),
                ]);
            }

            $seen[] = $current;
            $next = static::query()->whereKey($current)->value('canonical_product_id');
            $current = is_int($next) ? $next : (is_string($next) && ctype_digit($next) ? (int) $next : null);
        }
    }

    private function integerKey(): ?int
    {
        $key = $this->getKey();

        return is_int($key) ? $key : null;
    }
}
