<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $product_id
 * @property string $locale
 * @property string $title
 * @property string $slug
 * @property string|null $description
 */
class ProductTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'product_translations';

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
        static::saving(function (ProductTranslation $translation): void {
            if ($translation->siblingSlugTaken()) {
                throw ValidationException::withMessages([
                    'slug' => __('product::fields.slug_sibling_taken'),
                ]);
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function siblingSlugTaken(): bool
    {
        if ($this->slug === '') {
            return false;
        }

        $categoryId = Product::query()->whereKey($this->product_id)->value('product_category_id');

        return static::query()
            ->where('locale', $this->locale)
            ->where('slug', $this->slug)
            ->when($this->exists, fn (Builder $query): Builder => $query->whereKeyNot($this->getKey()))
            ->whereHas('product', function (Builder $query) use ($categoryId): void {
                if ($categoryId === null) {
                    $query->whereNull('product_category_id');

                    return;
                }

                $query->where('product_category_id', $categoryId);
            })
            ->exists();
    }
}
