<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Closure;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;

final class RelationValueRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function messages(FieldDefinition $field, mixed $value, string $path): array
    {
        $entity = self::relatedEntity($field);

        if ($entity === null) {
            return [];
        }

        $multiple = self::isMultiple($field);
        $messages = [];

        if (($field->validation['required'] ?? false) === true && self::isEmpty($value, $multiple)) {
            $messages[$path] = [
                __('validation.required', ['attribute' => $field->label]),
            ];
        }

        if (self::isEmpty($value, $multiple)) {
            return $messages;
        }

        $ids = self::normalizeIds($value, $multiple);

        if ($ids === []) {
            $messages[$path] = [
                __('builder::builder.validation.invalid_relation'),
            ];

            return $messages;
        }

        $min = self::normalizeLimit($field->config['min'] ?? null);
        $max = self::normalizeLimit($field->config['max'] ?? null);

        if ($multiple && $min !== null && count($ids) < $min) {
            $messages[$path] = [
                __('builder::builder.validation.relation_min', [
                    'min' => $min,
                    'attribute' => $field->label,
                ]),
            ];
        }

        if ($multiple && $max !== null && count($ids) > $max) {
            $messages[$path] = [
                __('builder::builder.validation.relation_max', [
                    'max' => $max,
                    'attribute' => $field->label,
                ]),
            ];
        }

        $resolver = app(RelationTargetResolver::class);

        foreach ($ids as $id) {
            if (! $resolver->recordExists($entity, $id)) {
                $messages[$path] = [
                    __('builder::builder.validation.invalid_relation_target'),
                ];

                break;
            }
        }

        return $messages;
    }

    public static function assertValid(FieldDefinition $field, mixed $value, ?string $path = null): void
    {
        $path ??= $field->name;
        $messages = self::messages($field, $value, $path);

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    /**
     * @return list<Closure>
     */
    public static function rules(FieldDefinition $field): array
    {
        return [
            fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($field): void {
                foreach (self::messages($field, $value, $attribute) as $messages) {
                    foreach ($messages as $message) {
                        $fail($message);
                    }
                }
            },
        ];
    }

    public static function relatedEntity(FieldDefinition $field): ?string
    {
        $entity = $field->config['related_entity'] ?? null;

        return filled($entity) ? (string) $entity : null;
    }

    public static function isMultiple(FieldDefinition $field): bool
    {
        return (bool) ($field->config['multiple'] ?? false);
    }

    public static function isEmpty(mixed $value, bool $multiple): bool
    {
        if ($multiple) {
            return ! is_array($value) || $value === [];
        }

        return blank($value);
    }

    /**
     * @return list<int|string>
     */
    public static function normalizeIds(mixed $value, bool $multiple): array
    {
        if ($multiple) {
            if (! is_array($value)) {
                return [];
            }

            return array_values(array_filter($value, fn (mixed $id): bool => filled($id)));
        }

        return filled($value) ? [$value] : [];
    }

    protected static function normalizeLimit(mixed $limit): ?int
    {
        if (! is_numeric($limit)) {
            return null;
        }

        $limit = (int) $limit;

        return $limit >= 0 ? $limit : null;
    }
}
