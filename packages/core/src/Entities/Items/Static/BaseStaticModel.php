<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Localization\Models\Localization;

/**
 * Lean astrotomic translatable base for static reference data (no draft/publishing machinery).
 *
 * @method BaseStaticTranslationModel|null translate(?string $locale = null, ?bool $withFallback = null)
 * @method BaseStaticTranslationModel translateOrNew(?string $locale = null)
 * @method Builder whereTranslation(string $attribute, mixed $value, ?string $locale = null, string $method = 'whereHas')
 */
abstract class BaseStaticModel extends Model implements TranslatableContract
{
    use Translatable {
        translate as protected astTranslate;
        translateOrNew as protected astTranslateOrNew;
    }

    /** @var list<string> */
    public $translatedAttributes = [];

    public bool $useTranslationFallback = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->translatedAttributes = $this->getCustomTranslatedAttributes();
    }

    /**
     * @return list<string>
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'common_name',
            'description',
        ];
    }

    public function translate(?string $locale = null, ?bool $withFallback = null): ?BaseStaticTranslationModel
    {
        /** @var BaseStaticTranslationModel|null $translation */
        $translation = $this->astTranslate($locale, $withFallback ?? $this->useFallback());

        return $translation;
    }

    public function translateOrNew(?string $locale = null): BaseStaticTranslationModel
    {
        /** @var BaseStaticTranslationModel $translation */
        $translation = $this->astTranslateOrNew($locale);

        return $translation;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereTranslationLabel(Builder $query, string $attribute, string $value, string $locale): Builder
    {
        return $query->whereTranslation($attribute, $value, $locale);
    }

    public static function normalizeTranslationLabel(string $label): string
    {
        $normalized = mb_strtolower(trim($label));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s*\([^)]*\)\s*$/u', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Map an admin locale_variant (e.g. de_DE) to the codelist translation locale (e.g. de).
     */
    public static function resolveTranslationLocale(string $localeVariant): string
    {
        $localization = Localization::query()
            ->with('language')
            ->where('locale_variant', $localeVariant)
            ->first();

        if ($localization?->language !== null) {
            return $localization->language->alpha2;
        }

        if (str_contains($localeVariant, '_')) {
            return explode('_', $localeVariant)[0];
        }

        return $localeVariant;
    }

    public static function resolveCodeByTranslation(string $label, string $locale, string $attribute = 'common_name'): ?string
    {
        $locale = static::resolveTranslationLocale($locale);

        /** @var static|null $record */
        $record = static::query()
            ->whereTranslation($attribute, $label, $locale)
            ->first();

        if ($record !== null) {
            return (string) $record->code;
        }

        $normalizedLabel = static::normalizeTranslationLabel($label);

        /** @var iterable<int, static> $records */
        $records = static::query()->with(['translations' => function ($query) use ($locale): void {
            $query->where('locale', $locale);
        }])->get();

        foreach ($records as $candidate) {
            $translation = $candidate->translate($locale, false);

            if ($translation === null) {
                continue;
            }

            $value = $translation->getAttribute($attribute);

            if (! is_string($value)) {
                continue;
            }

            if (static::normalizeTranslationLabel($value) === $normalizedLabel) {
                return (string) $candidate->code;
            }
        }

        return null;
    }

    public function translatedLabel(string $locale, string $attribute = 'common_name'): ?string
    {
        $translation = $this->translate($locale, true);

        if ($translation === null) {
            return null;
        }

        $value = $translation->getAttribute($attribute);

        return is_string($value) ? $value : null;
    }

    protected function getFallbackLocale(?string $locale = null): ?string
    {
        if ($locale !== null && $this->getLocalesHelper()->isLocaleCountryBased($locale)) {
            $countryFallback = $this->getLocalesHelper()->getLanguageFromCountryBasedLocale($locale);

            if ($countryFallback !== null) {
                return $countryFallback;
            }
        }

        return 'en';
    }
}
