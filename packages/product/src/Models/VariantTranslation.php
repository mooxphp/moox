<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Publish\Models\HasPublish;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

/**
 * @property int $variant_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $slug
 * @property string|null $description
 * @property int $created_by_id
 * @property string $created_by_type
 * @property int $updated_by_id
 * @property string $updated_by_type
 */
class VariantTranslation extends Model
{
    use HasAudit;
    use HasPublish;
    use HasActive;
    use HasSoftDelete;

    protected $table = 'variant_translations';

    protected $attributes = [
        'is_active' => true,
    ];

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
        static::saving(function (VariantTranslation $translation): void {
            foreach (['title', 'slug', 'description'] as $attribute) {
                if (($translation->attributes[$attribute] ?? null) === '') {
                    $translation->attributes[$attribute] = null;
                }
            }

            $translation->keepOnlyOwnTranslation();
        });
    }

    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if (! in_array($key, ['title', 'slug', 'description'], true) || ! $this->isEmptyTranslationValue($value)) {
            return $value;
        }

        $inherited = $this->productTranslationValue($key);

        return $this->isEmptyTranslationValue($inherited) ? $value : $inherited;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
        ];
    }

    /**
     * @return BelongsTo<Variant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    private function keepOnlyOwnTranslation(): void
    {
        $product = $this->productForInheritance();

        if (! $product instanceof Product || $this->locale === '') {
            return;
        }

        $productTranslation = $product->translate($this->locale, false);

        foreach (['title', 'slug', 'description'] as $attribute) {
            $stored = $this->attributes[$attribute] ?? null;

            if ($this->isEmptyTranslationValue($stored)) {
                $this->attributes[$attribute] = null;

                continue;
            }

            $parent = $productTranslation instanceof Model
                ? ($productTranslation->getAttributes()[$attribute] ?? null)
                : null;

            if ($this->sameTranslationValue($stored, $parent)) {
                $this->attributes[$attribute] = null;
            }
        }
    }

    private function productTranslationValue(string $key): mixed
    {
        $product = $this->productForInheritance();

        if (! $product instanceof Product || $this->locale === '') {
            return null;
        }

        $productTranslation = $product->translate($this->locale, false);

        if (! $productTranslation instanceof Model) {
            return null;
        }

        return $productTranslation->getAttributes()[$key] ?? null;
    }

    private function productForInheritance(): ?Product
    {
        $variant = $this->relationLoaded('variant') ? $this->getRelation('variant') : $this->variant()->first();

        if (! $variant instanceof Variant) {
            return null;
        }

        $product = $variant->relationLoaded('product') ? $variant->getRelation('product') : $variant->product()->first();

        return $product instanceof Product ? $product : null;
    }

    private function isEmptyTranslationValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function sameTranslationValue(mixed $stored, mixed $parent): bool
    {
        if ($this->isEmptyTranslationValue($stored) || $this->isEmptyTranslationValue($parent)) {
            return false;
        }

        if (! is_string($stored) || ! is_string($parent)) {
            return false;
        }

        return $stored === $parent;
    }
}
