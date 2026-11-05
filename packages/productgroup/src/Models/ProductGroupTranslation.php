<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $product_group_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $description
 */
class ProductGroupTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'product_group_translations';

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
        static::saving(function (ProductGroupTranslation $translation): void {
            if ($translation->siblingSlugTaken()) {
                throw ValidationException::withMessages([
                    'slug' => __('productgroup::fields.slug_sibling_taken'),
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<ProductGroup, $this>
     */
    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function siblingSlugTaken(): bool
    {
        if ($this->slug === '') {
            return false;
        }

        $parentId = ProductGroup::query()->whereKey($this->product_group_id)->value('parent_id');

        return static::query()
            ->where('locale', $this->locale)
            ->where('slug', $this->slug)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->whereHas('productGroup', function (Builder $query) use ($parentId): void {
                if ($parentId === null) {
                    $query->whereNull('parent_id');

                    return;
                }

                $query->where('parent_id', $parentId);
            })
            ->exists();
    }
}
