<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Product\Database\Factories\ProductFactory;

/**
 * @property string $sku
 * @property string|float $price
 * @property string|float|null $sale_price
 * @property int $stock
 * @property string $status
 * @property int|null $brand_id
 * @property string|float|null $weight
 * @property array|null $meta
 * @property string[] $translatedAttributes
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $short_description
 * @property-read string|null $description
 * @property-read string|null $meta_title
 * @property-read string|null $meta_description
 */
class Product extends BaseDraftModel
{
    use HasFactory;

    /**
     * {@inheritdoc}
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'name',
            'slug',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'price',
        'sale_price',
        'stock',
        'status',
        'brand_id',
        'weight',
        'meta',
        'uuid',
        'ulid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'integer',
            'brand_id' => 'integer',
            'weight' => 'decimal:3',
            'meta' => 'json',
        ];
    }

    public static function getResourceName(): string
    {
        return 'product';
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
