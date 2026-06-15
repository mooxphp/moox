<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Closure;
use Moox\Builder\Data\FieldDefinition;

final class OptionValueRules
{
    /**
     * @return list<string|Closure>
     */
    public static function forField(FieldDefinition $field): array
    {
        if (! in_array($field->type, ['select', 'radio', 'multiselect', 'checkbox_list'], true)) {
            return [];
        }

        $allowed = self::allowedValues($field);

        if ($allowed === []) {
            return [];
        }

        if (in_array($field->type, ['multiselect', 'checkbox_list'], true)) {
            return [
                'array',
                self::arrayValuesRule($allowed),
            ];
        }

        return [
            self::scalarValueRule($allowed),
        ];
    }

    public static function assertValid(FieldDefinition $field, mixed $value): void
    {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        $allowed = self::allowedValues($field);

        if ($allowed === []) {
            return;
        }

        if (in_array($field->type, ['multiselect', 'checkbox_list'], true)) {
            if (! is_array($value)) {
                throw new \InvalidArgumentException(__('builder::builder.validation.invalid_option'));
            }

            foreach ($value as $item) {
                if (! in_array((string) $item, $allowed, true)) {
                    throw new \InvalidArgumentException(__('builder::builder.validation.invalid_option'));
                }
            }

            return;
        }

        if (! in_array((string) $value, $allowed, true)) {
            throw new \InvalidArgumentException(__('builder::builder.validation.invalid_option'));
        }
    }

    /**
     * @return list<string>
     */
    public static function allowedValues(FieldDefinition $field): array
    {
        return collect($field->options)
            ->pluck('value')
            ->map(fn (mixed $value): string => (string) $value)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function scalarValueRule(array $allowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! in_array((string) $value, $allowed, true)) {
                $fail(__('builder::builder.validation.invalid_option'));
            }
        };
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function arrayValuesRule(array $allowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
            if ($value === null) {
                return;
            }

            if (! is_array($value)) {
                $fail(__('builder::builder.validation.invalid_option'));

                return;
            }

            foreach ($value as $item) {
                if (! in_array((string) $item, $allowed, true)) {
                    $fail(__('builder::builder.validation.invalid_option'));

                    return;
                }
            }
        };
    }
}
