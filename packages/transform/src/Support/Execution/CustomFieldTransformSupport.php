<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Moox\Builder\Services\CustomFieldsManager;

/**
 * Optional Builder custom-field support for transform destinations.
 *
 * moox/transform has no composer dependency on moox/builder. When builder is
 * installed, models may expose customFieldNames() + setCustomFields(); locale-aware
 * writes use CustomFieldsManager only when that class exists at runtime.
 */
final class CustomFieldTransformSupport
{
    public const string VALUES_LOCALE_FIELD = '_builder_values_locale';

    public static function supports(Model $destination): bool
    {
        return method_exists($destination, 'customFieldNames')
            && method_exists($destination, 'setCustomFields');
    }

    /**
     * @return list<string>
     */
    public static function fieldNames(Model $destination): array
    {
        if (! method_exists($destination, 'customFieldNames')) {
            return [];
        }

        /** @var list<string> $names */
        $names = $destination::customFieldNames();

        return $names;
    }

    public static function isCustomField(Model $destination, string $field): bool
    {
        return in_array($field, self::fieldNames($destination), true);
    }

    /**
     * @return list<string>
     */
    public static function metaFieldNames(Model $destination): array
    {
        return self::supports($destination) ? [self::VALUES_LOCALE_FIELD] : [];
    }

    /**
     * @param  array<string, mixed>  $resolvedData
     * @return array{
     *     model_data: array<string, mixed>,
     *     custom_fields: array<string, mixed>,
     *     custom_fields_locale: ?string
     * }
     */
    public static function partitionResolvedData(Model $destination, array $resolvedData): array
    {
        if (! self::supports($destination)) {
            return [
                'model_data' => $resolvedData,
                'custom_fields' => [],
                'custom_fields_locale' => null,
            ];
        }

        $customFields = [];
        $modelData = [];
        $locale = null;

        foreach ($resolvedData as $field => $value) {
            if ($field === self::VALUES_LOCALE_FIELD) {
                if (is_string($value) && $value !== '') {
                    $locale = $value;
                }

                continue;
            }

            if (self::isCustomField($destination, $field)) {
                $customFields[$field] = $value;

                continue;
            }

            $modelData[$field] = $value;
        }

        return [
            'model_data' => $modelData,
            'custom_fields' => $customFields,
            'custom_fields_locale' => $locale,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function persistAfterSave(Model $destination, array $values, ?string $locale = null): void
    {
        if ($values === [] || ! self::supports($destination)) {
            return;
        }

        $values = self::filterMergeableValues($destination, $values);

        if ($values === []) {
            return;
        }

        if ($locale !== null && self::canUseBuilderManager()) {
            self::persistViaBuilderManager($destination, $values, $locale);

            return;
        }

        $destination->setCustomFields($values);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function filterMergeableValues(Model $destination, array $values): array
    {
        $filtered = [];

        foreach ($values as $field => $value) {
            if (self::shouldSkipMergeValue($destination, $value)) {
                continue;
            }

            $filtered[$field] = $value;
        }

        return $filtered;
    }

    private static function shouldSkipMergeValue(Model $destination, mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }

    private static function canUseBuilderManager(): bool
    {
        return class_exists(CustomFieldsManager::class);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function persistViaBuilderManager(Model $destination, array $values, string $locale): void
    {
        $manager = app(CustomFieldsManager::class);
        $entity = self::resolveCustomFieldsEntity($destination);
        $fields = $manager->fieldsForEntity($entity)->keyBy('name');
        $payload = array_intersect_key($values, $fields->all());

        if ($payload === []) {
            return;
        }

        $manager->saveValues(
            $entity,
            $destination,
            $payload,
            $fields->only(array_keys($payload))->values(),
            $locale,
        );

        if (method_exists($destination, 'flushCustomFieldsCache')) {
            $destination->flushCustomFieldsCache();
        }
    }

    private static function resolveCustomFieldsEntity(Model $destination): string
    {
        if (method_exists($destination, 'getResourceName')) {
            return $destination::getResourceName();
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $destination::class;

        return Str::kebab(class_basename($modelClass));
    }
}
