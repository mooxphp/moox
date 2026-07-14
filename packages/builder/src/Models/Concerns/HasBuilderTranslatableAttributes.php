<?php

declare(strict_types=1);

namespace Moox\Builder\Models\Concerns;

use Astrotomic\Translatable\Translatable;
use Moox\Builder\Support\BuilderLocaleResolver;

trait HasBuilderTranslatableAttributes
{
    use Translatable {
        fill as translatableFill;
        saveTranslations as protected persistTranslations;
    }

    public static function bootHasBuilderTranslatableAttributes(): void
    {
        static::saving(function (self $model): void {
            $model->mirrorDefaultLocaleTranslationsToMainRecord();
        });
    }

    public function saveTranslations(): bool
    {
        return $this->persistTranslations();
    }

    public function usesTranslationFallback(): bool
    {
        return true;
    }

    public function fill(array $attributes)
    {
        $resolver = app(BuilderLocaleResolver::class);
        $mirrored = [];

        foreach ($this->translatedAttributes as $attribute) {
            if (
                array_key_exists($attribute, $attributes)
                && $resolver->current() === $resolver->defaultLocale()
            ) {
                $mirrored[$attribute] = $attributes[$attribute];
            }
        }

        $result = $this->translatableFill($attributes);

        foreach ($mirrored as $attribute => $value) {
            $this->attributes[$attribute] = $value;
        }

        return $result;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->translatedAttributes, true)) {
            $locale = app(BuilderLocaleResolver::class)->current();

            $this->translateOrNew($locale)->$key = $value;

            if ($locale === app(BuilderLocaleResolver::class)->defaultLocale()) {
                return parent::setAttribute($key, $value);
            }

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function syncTranslationFallbackOnMainRecord(string $locale, array $attributes): void
    {
        if ($locale !== app(BuilderLocaleResolver::class)->defaultLocale()) {
            return;
        }

        $this->update($attributes);
    }

    protected function locale(): string
    {
        return app(BuilderLocaleResolver::class)->defaultLocale();
    }

    protected function mirrorDefaultLocaleTranslationsToMainRecord(): void
    {
        $defaultLocale = app(BuilderLocaleResolver::class)->defaultLocale();

        foreach ($this->translatedAttributes as $attribute) {
            if (filled($this->attributes[$attribute] ?? null)) {
                continue;
            }

            $translation = $this->translations->firstWhere($this->getLocaleKey(), $defaultLocale)
                ?? $this->translations->first();

            $value = $translation?->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                $this->attributes[$attribute] = $value;
            }
        }
    }
}
