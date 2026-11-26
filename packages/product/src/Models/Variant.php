<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Contracts\InheritsCustomFields;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Media\Models\Media;
use Moox\Media\Traits\HasMediaUsable;
use Moox\Product\Database\Factories\VariantFactory;
use Moox\Product\Enums\ProductType;
use Moox\Support\Active\Models\HasActive;
use Moox\Builder\Concerns\InteractsWithTranslatedCustomFields;
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
 * @property int $product_id
 * @property string|null $sku
 * @property bool $is_orderable
 * @property bool $is_active
 * @property int $sort_order
 * @property int|null $manufacturer_id
 * @property string|null $manufacturer_name
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
 * @property-read Product|null $product
 */
class Variant extends Model implements HasMedia, InheritsCustomFields, TranslatableContract
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasPublish;

    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    use HasIdentifier;
    use HasMediaUsable;
    use HasRelations;
    use InteractsWithTranslatedCustomFields;
    use InteractsWithMedia;
    use HasSoftDelete;

    protected $table = 'variants';

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
        'product_id',
        'sku',
        'is_orderable',
        'sort_order',
        'manufacturer_id',
        'manufacturer_name',
        'note',
        'image',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'is_orderable' => false,
        'sort_order' => 0,
    ];

    public static function getResourceName(): string
    {
        return 'variant';
    }

    /**
     * @var list<string>
     */
    private const INHERITED_ATTRIBUTES = [
        'sku',
        'external_reference',
        'external_info',
        'external_status',
        'note',
        'image',
        'data',
        'published_at',
        'published_by_id',
        'published_by_type',
        'unpublished_at',
        'unpublished_by_id',
        'unpublished_by_type',
    ];

    public function getAttribute($key): mixed
    {
        if ($this->inheritsAttribute($key) && $this->storedIsEmpty($key)) {
            $inherited = $this->inheritedValue($key);

            if (! $this->isEmptyValue($inherited)) {
                return $inherited;
            }
        }

        if (is_string($key) && $key !== '' && $this->shouldDelegateToCustomField($key)) {
            return $this->customField($key);
        }

        return $this->getTranslatedAttribute($key);
    }

    /**
     * Filament fills from attributesToArray(), which skips getAttribute().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mergeInheritedAttributesForForm(array $data): array
    {
        foreach (self::INHERITED_ATTRIBUTES as $attribute) {
            if (! $this->isEmptyValue($data[$attribute] ?? null)) {
                continue;
            }

            $inherited = $this->getAttribute($attribute);

            if (! $this->isEmptyValue($inherited)) {
                $data[$attribute] = $inherited;
            }
        }

        return $data;
    }

    public function customFieldsParent(): ?Model
    {
        return $this->inheritedProduct();
    }

    public function customFieldsParentEntity(): string
    {
        return 'product';
    }

    protected static function booted(): void
    {
        static::saving(function (Variant $variant): void {
            $variant->normalizeFields();
            $variant->assertRules();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'is_orderable' => 'boolean',
            'sort_order' => 'integer',
            'manufacturer_id' => 'integer',
            'image' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    protected static function newFactory(): VariantFactory
    {
        return VariantFactory::new();
    }

    public function normalizeFields(): void
    {
        if (($this->attributes['sku'] ?? null) === '') {
            $this->attributes['sku'] = null;
        }

        if ($this->manufacturer_name === '') {
            $this->manufacturer_name = null;
        }

        if ($this->manufacturer_id === 0) {
            $this->manufacturer_id = null;
        }

        $product = $this->inheritedProduct();

        if (! $product instanceof Product) {
            return;
        }

        foreach (self::INHERITED_ATTRIBUTES as $attribute) {
            if ($this->storedIsEmpty($attribute)) {
                continue;
            }

            if ($this->sameAsProduct($attribute, $product)) {
                $this->attributes[$attribute] = null;
            }
        }
    }

    public function assertRules(): void
    {
        $messages = [];

        $product = $this->inheritedProduct(fresh: true);

        if (! $product instanceof Product) {
            $messages['product_id'] = $this->product_id > 0
                ? __('product::fields.product_not_simple')
                : __('product::fields.product_required');
        }

        $storedSku = $this->attributes['sku'] ?? null;

        if (is_string($storedSku) && $storedSku !== '' && $this->skuTaken($storedSku)) {
            $messages['sku'] = __('product::fields.sku_taken');
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function skuTaken(string $sku): bool
    {
        return static::query()
            ->where('sku', $sku)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    private function inheritsAttribute(string $key): bool
    {
        return in_array($key, self::INHERITED_ATTRIBUTES, true);
    }

    private function storedIsEmpty(string $key): bool
    {
        if (! array_key_exists($key, $this->attributes)) {
            return true;
        }

        return $this->isEmptyValue($this->attributes[$key]);
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        return $trimmed === '' || $trimmed === '[]' || $trimmed === '{}' || $trimmed === 'null';
    }

    private function inheritedValue(string $key): mixed
    {
        $product = $this->inheritedProduct();

        if (! $product instanceof Product) {
            return null;
        }

        return $product->getAttribute($key);
    }

    private function sameAsProduct(string $attribute, Product $product): bool
    {
        $stored = $this->attributes[$attribute] ?? null;
        $parent = $product->getAttribute($attribute);

        if ($this->isEmptyValue($stored) || $this->isEmptyValue($parent)) {
            return false;
        }

        if (is_array($stored) || is_array($parent) || in_array($attribute, ['image', 'data'], true)) {
            return json_encode($this->decodedValue($stored)) === json_encode($this->decodedValue($parent));
        }

        $storedString = $this->stringComparable($stored);
        $parentString = $this->stringComparable($parent);

        return $storedString !== null
            && $parentString !== null
            && $storedString === $parentString;
    }

    private function stringComparable(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return null;
    }

    private function decodedValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $value;
    }

    private function inheritedProduct(bool $fresh = false): ?Product
    {
        if ($this->product_id < 1) {
            return null;
        }

        $product = $fresh
            ? Product::query()->find($this->product_id)
            : ($this->relationLoaded('product') ? $this->getRelation('product') : $this->product()->first());

        return $product instanceof Product && $product->type === ProductType::Simple
            ? $product
            : null;
    }
}
