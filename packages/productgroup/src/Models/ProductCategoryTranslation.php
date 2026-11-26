<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $product_category_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $description
 */
class ProductCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'product_category_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductCategoryTranslation $translation): void {
            if ($translation->siblingSlugTaken()) {
                throw ValidationException::withMessages([
                    'slug' => __('productcategory::fields.slug_sibling_taken'),
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function siblingSlugTaken(): bool
    {
        if ($this->slug === '') {
            return false;
        }

        $parentId = ProductCategory::query()->whereKey($this->product_category_id)->value('parent_id');

        return static::query()
            ->where('locale', $this->locale)
            ->where('slug', $this->slug)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->whereHas('productCategory', function (Builder $query) use ($parentId): void {
                if ($parentId === null) {
                    $query->whereNull('parent_id');

                    return;
                }

                $query->where('parent_id', $parentId);
            })
            ->exists();
    }
}
