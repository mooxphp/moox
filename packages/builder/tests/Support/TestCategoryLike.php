<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mimics Moox category-like models where the title lives in translations and
 * display_title is resolved through an accessor.
 */
class TestCategoryLike extends Model
{
    public array $translatedAttributes = ['title'];

    public $timestamps = false;

    protected $table = 'categories';

    protected $guarded = [];

    public function translations(): HasMany
    {
        return $this->hasMany(TestCategoryLikeTranslation::class, 'category_id');
    }

    public function getDisplayTitleAttribute(): string
    {
        $locale = request()->query('lang') ?? 'en_US';
        $translation = $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();

        if ($translation !== null && filled($translation->title)) {
            return (string) $translation->title;
        }

        return 'ID: '.$this->id;
    }
}
