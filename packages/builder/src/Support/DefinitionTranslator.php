<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Data\FieldGroupDefinition;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldOption;

final class DefinitionTranslator
{
    public function __construct(
        protected BuilderLocaleResolver $localeResolver,
    ) {}

    public function localizeGroup(FieldGroupDefinition $group, ?string $locale = null): FieldGroupDefinition
    {
        $locale = $this->localeResolver->current($locale);

        return new FieldGroupDefinition(
            name: $this->resolveGroupName($group->name, $group->translations, $locale),
            slug: $group->slug,
            placement: $group->placement,
            fields: $group->fields
                ->map(fn (FieldDefinition $field): FieldDefinition => $this->localizeField($field, $locale))
                ->values(),
            locationRules: $group->locationRules,
            settings: $group->settings,
            translations: $group->translations,
        );
    }

    public function localizeField(FieldDefinition $field, ?string $locale = null): FieldDefinition
    {
        $locale = $this->localeResolver->current($locale);

        return new FieldDefinition(
            name: $field->name,
            label: $this->resolveLabel($field->label, $field->translations, $locale, 'label'),
            type: $field->type,
            sort: $field->sort,
            config: $this->resolveConfig($field->config, $field->translations, $locale),
            validation: $field->validation,
            settings: $field->settings,
            options: collect($field->options)
                ->map(function (array $option) use ($locale): array {
                    /** @var array{label: string, value: string, translations?: array<string, array{label?: string}>} $option */
                    return [
                        'label' => $this->resolveLabel(
                            $option['label'],
                            $option['translations'] ?? [],
                            $locale,
                            'label',
                        ),
                        'value' => $option['value'],
                    ];
                })
                ->values()
                ->all(),
            children: $field->children
                ->map(fn (FieldDefinition $child): FieldDefinition => $this->localizeField($child, $locale))
                ->values(),
            translations: $field->translations,
        );
    }

    public function translatedGroupName(FieldGroup $group, ?string $locale = null): string
    {
        return $this->translatedAttribute($group, 'name', $group->name, $locale);
    }

    public function translatedFieldLabel(Field $field, ?string $locale = null): string
    {
        return $this->translatedAttribute($field, 'label', $field->label, $locale);
    }

    public function translatedOptionLabel(FieldOption $option, ?string $locale = null): string
    {
        return $this->translatedAttribute($option, 'label', $option->label, $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function translatedFieldConfig(Field $field, ?string $locale = null): array
    {
        $locale = $this->localeResolver->current($locale);
        $structuralConfig = array_diff_key(
            $field->config ?? [],
            array_flip(BuilderLocaleResolver::TRANSLATABLE_CONFIG_KEYS),
        );

        foreach ($this->localeResolver->fallbackChain($locale) as $candidate) {
            $translation = $this->translationRow($field, $candidate);
            $translatedConfig = is_object($translation) ? ($translation->config ?? null) : null;

            if (is_array($translatedConfig) && $translatedConfig !== []) {
                return array_merge($structuralConfig, $this->extractTranslatableConfig($translatedConfig));
            }
        }

        return $field->config ?? [];
    }

    /**
     * @param  array<string, array{name?: string}>  $translations
     */
    public function resolveGroupName(string $fallback, array $translations, ?string $locale = null): string
    {
        return $this->resolveLabel($fallback, $translations, $locale, 'name');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, array{label?: string, config?: array<string, mixed>}>  $translations
     * @return array<string, mixed>
     */
    public function resolveConfig(array $config, array $translations, ?string $locale = null): array
    {
        $locale = $this->localeResolver->current($locale);
        $translatedConfig = $this->translationValue($translations, $locale, 'config');

        if (! is_array($translatedConfig)) {
            foreach ($this->localeResolver->fallbackChain($locale) as $candidate) {
                if ($candidate === $locale) {
                    continue;
                }

                $translatedConfig = $this->translationValue($translations, $candidate, 'config');

                if (is_array($translatedConfig)) {
                    break;
                }
            }
        }

        if (! is_array($translatedConfig)) {
            return $config;
        }

        return array_merge($config, $this->extractTranslatableConfig($translatedConfig));
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    public function resolveLabel(string $fallback, array $translations, ?string $locale, string $key): string
    {
        $locale = $this->localeResolver->current($locale);

        foreach ($this->localeResolver->fallbackChain($locale) as $candidate) {
            $value = $this->translationValue($translations, $candidate, $key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function extractTranslatableConfig(array $config): array
    {
        return array_intersect_key($config, array_flip(BuilderLocaleResolver::TRANSLATABLE_CONFIG_KEYS));
    }

    /**
     * @param  Model&TranslatableContract  $model
     */
    protected function translatedAttribute(Model $model, string $attribute, string $fallback, ?string $locale = null): string
    {
        $locale = $this->localeResolver->current($locale);

        foreach ($this->localeResolver->fallbackChain($locale) as $candidate) {
            $value = $this->translationAttribute($model, $candidate, $attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * @param  Model&TranslatableContract  $model
     */
    protected function translationAttribute(Model $model, string $locale, string $attribute): mixed
    {
        if ($model->relationLoaded('translations')) {
            $localeKey = $model->getLocaleKey();

            return $model->translations->firstWhere($localeKey, $locale)?->getAttribute($attribute);
        }

        return $model->translate($locale, false)?->getAttribute($attribute);
    }

    /**
     * @param  Model&TranslatableContract  $model
     */
    protected function translationRow(Model $model, string $locale): ?Model
    {
        if ($model->relationLoaded('translations')) {
            $localeKey = $model->getLocaleKey();

            return $model->translations->firstWhere($localeKey, $locale);
        }

        return $model->translate($locale, false);
    }

    /**
     * @param  array<string, array<string, mixed>>  $translations
     */
    protected function translationValue(array $translations, string $locale, string $key): mixed
    {
        if (! array_key_exists($locale, $translations)) {
            return null;
        }

        return $translations[$locale][$key] ?? null;
    }
}
