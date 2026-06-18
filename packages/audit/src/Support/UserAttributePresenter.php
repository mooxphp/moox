<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

use Illuminate\Database\Eloquent\Model;

final class UserAttributePresenter
{
    /**
     * @param  array<string, array{title_attribute?: string, label?: string}>  $userModels
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public static function enrichProperties(array $changes, array $userModels): array
    {
        $properties = [];

        foreach (['author_id', 'created_by_id', 'updated_by_id'] as $attribute) {
            $typeAttribute = str_replace('_id', '_type', $attribute);

            if (! array_key_exists($attribute, $changes['attributes'] ?? $changes['old'] ?? [])) {
                continue;
            }

            $id = $changes['attributes'][$attribute] ?? $changes['old'][$attribute] ?? null;
            $type = $changes['attributes'][$typeAttribute] ?? $changes['old'][$typeAttribute] ?? null;

            if ($id === null || $type === null || ! class_exists($type)) {
                continue;
            }

            $label = self::resolveLabel($type, (string) $id, $userModels);

            if ($label !== null) {
                $properties[str_replace('_id', '_label', $attribute)] = $label;
            }
        }

        return $properties;
    }

    /**
     * @param  array<string, array{title_attribute?: string, label?: string}>  $userModels
     */
    public static function resolveLabel(string $modelClass, string|int $id, array $userModels): ?string
    {
        $config = $userModels[$modelClass] ?? null;
        $titleAttribute = is_array($config) ? ($config['title_attribute'] ?? 'name') : 'name';
        $typeLabel = is_array($config) ? ($config['label'] ?? class_basename($modelClass)) : class_basename($modelClass);

        /** @var Model|null $record */
        $record = $modelClass::query()->find($id);

        if (! $record instanceof Model) {
            return null;
        }

        $name = $record->getAttribute($titleAttribute);

        if (! is_string($name) || $name === '') {
            return (string) $id;
        }

        return "{$name} ({$typeLabel})";
    }
}
