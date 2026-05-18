<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Attribute\Models\Concerns\HasAttributeValues;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Product\Database\Factories\ProductFactory;

/**
 * @property bool $is_active
 * @property array|null $image
 * @property string|null $type
 * @property Carbon|null $due_at
 * @property string|null $color
 * @property string|null $sku
 * @property string|null $gtin
 * @property string|null $mpn
 * @property string|null $brand_name
 * @property int|null $weight_grams
 * @property int|null $length_mm
 * @property int|null $width_mm
 * @property int|null $height_mm
 * @property string[] $translatedAttributes
 * @property string $uuid
 * @property string $ulid
 * @property string|null $status
 * @property array|null $custom_properties
 * @property-read string $title
 * @property-read string $slug
 * @property-read string $permalink
 * @property-read string|null $subtitle
 * @property-read string|null $excerpt
 * @property-read string|null $description
 * @property-read string|null $content
 * @property-read string|null $meta_title
 * @property-read string|null $meta_description
 * @property-read int|null $author_id
 * @property-read string|null $author_type
 * @property-read User|null $author
 */
class Product extends BaseDraftModel
{
    use HasAttributeValues, HasFactory, HasModelTaxonomy;

    /**
     * {@inheritdoc}
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'slug',
            'permalink',
            'subtitle',
            'excerpt',
            'description',
            'content',
            'meta_title',
            'meta_description',
            'author_id',
            'author_type',
        ];
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'image',
        'type',
        'color',
        'sku',
        'gtin',
        'mpn',
        'brand_name',
        'weight_grams',
        'length_mm',
        'width_mm',
        'height_mm',
        'due_at',
        'uuid',
        'ulid',
        'status',
        'custom_properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'image' => 'json',
            'due_at' => 'datetime',
            'uuid' => 'string',
            'ulid' => 'string',
            'custom_properties' => 'json',
            'weight_grams' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
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
