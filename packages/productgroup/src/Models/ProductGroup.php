<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Media\Models\Media;
use Moox\Media\Traits\HasMediaUsable;
use Moox\ProductGroup\Database\Factories\ProductGroupFactory;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rgt
 * @property bool $is_active
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
 * @property-read ProductGroup|null $parent
 */
class ProductGroup extends Model implements HasMedia, TranslatableContract
{
    use HasAudit;

    /** @use HasFactory<ProductGroupFactory> */
    use HasFactory;

    use HasIdentifier;
    use HasMediaUsable;
    use HasRelations;
    use InteractsWithMedia;
    use SoftDeletes;
    use Translatable;

    protected $table = 'product_groups';

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
        'lft',
        'rgt',
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
        'is_active' => true,
        'lft' => 0,
        'rgt' => 0,
        'sort_order' => 0,
    ];

    public static function getResourceName(): string
    {
        return 'productgroup';
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

    protected static function newFactory(): ProductGroupFactory
    {
        return ProductGroupFactory::new();
    }
}
