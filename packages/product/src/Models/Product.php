<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Product\Database\Factories\ProductFactory;

/**
 * @property string $sku
 * @property string $type
 * @property string $status
 * @property string|float $price
 * @property string|float|null $sale_price
 * @property string|float|null $cost_price
 * @property int $stock
 * @property int $stock_min
 * @property string|float|null $weight
 * @property string|null $weight_unit
 * @property string|null $unit_of_measure
 * @property bool $is_purchasable
 * @property bool $is_sellable
 * @property array|null $custom_properties
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
    use HasModelTaxonomy;

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
        'type',
        'status',
        'price',
        'sale_price',
        'cost_price',
        'stock',
        'stock_min',
        'weight',
        'weight_unit',
        'unit_of_measure',
        'is_purchasable',
        'is_sellable',
        'custom_properties',
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
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'stock_min' => 'integer',
            'weight' => 'decimal:3',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'custom_properties' => 'array',
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
