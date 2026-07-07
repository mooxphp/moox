<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\ProductGroup\Database\Factories\ProductGroupFactory;

/**
 * @property string $code
 * @property string $type
 * @property string $status
 * @property int|null $parent_id
 * @property int $_lft
 * @property int $_rgt
 * @property string|null $sku_prefix
 * @property array|null $custom_properties
 * @property string[] $translatedAttributes
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $short_description
 * @property-read string|null $description
 * @property-read string|null $meta_title
 * @property-read string|null $meta_description
 * @property-read ProductGroup|null $parent
 */
class ProductGroup extends BaseDraftModel
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
        'code',
        'type',
        'status',
        'parent_id',
        '_lft',
        '_rgt',
        'sku_prefix',
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
            'parent_id' => 'integer',
            '_lft' => 'integer',
            '_rgt' => 'integer',
            'custom_properties' => 'array',
        ];
    }

    public static function getResourceName(): string
    {
        return 'productgroup';
    }

    /**
     * @return BelongsTo<ProductGroup, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ProductGroup, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    protected static function newFactory(): ProductGroupFactory
    {
        return ProductGroupFactory::new();
    }
}
