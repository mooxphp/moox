<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Moox\Builder\Data\FieldDefinition;

/**
 * Evaluates ACF-style conditional visibility rules for custom fields.
 */
final class ConditionalLogic
{
    public const ACTION_SHOW = 'show';

    public const ACTION_HIDE = 'hide';

    public const LOGIC_AND = 'and';

    public const LOGIC_OR = 'or';

    /** @var list<string> */
    public const OPERATORS = [
        'equals',
        'not_equals',
        'empty',
        'not_empty',
        'contains',
    ];

    public static function isConfigured(FieldDefinition $field): bool
    {
        $conditions = $field->conditions();

        if (! ($conditions['enabled'] ?? false)) {
            return false;
        }

        return self::normalizedRules($conditions) !== [];
    }

    /**
     * @return list<string>
     */
    public static function triggerFieldNames(FieldDefinition $field): array
    {
        if (! self::isConfigured($field)) {
            return [];
        }

        return collect(self::normalizedRules($field->conditions()))
            ->pluck('field')
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (mixed $name): string => (string) $name)
            ->unique()
            ->values()
            ->all();
    }

    public static function passesForm(FieldDefinition $field, callable $get): bool
    {
        $values = [];

        foreach (self::triggerFieldNames($field) as $trigger) {
            $values[$trigger] = $get($trigger);
        }

        return self::isVisibleForValues($field, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function isVisibleForValues(FieldDefinition $field, array $values): bool
    {
        $conditions = $field->conditions();

        if (! ($conditions['enabled'] ?? false)) {
            return true;
        }

        $rules = self::normalizedRules($conditions);

        if ($rules === []) {
            return true;
        }

        $action = self::normalizeAction($conditions['action'] ?? self::ACTION_SHOW);
        $logic = self::normalizeLogic($conditions['logic'] ?? self::LOGIC_AND);
        $matches = self::combine(
            collect($rules)
                ->map(fn (array $rule): bool => self::matchesRule($rule, $values))
                ->all(),
            $logic,
        );

        return $action === self::ACTION_HIDE ? ! $matches : $matches;
    }

    /**
     * @return array{enabled: bool, action: string, logic: string, rules: list<array{field: string, operator: string, value: mixed}>}
     */
    public static function normalizeSettings(mixed $conditions): array
    {
        if (! is_array($conditions)) {
            $conditions = [];
        }

        return [
            'enabled' => (bool) ($conditions['enabled'] ?? false),
            'action' => self::normalizeAction($conditions['action'] ?? self::ACTION_SHOW),
            'logic' => self::normalizeLogic($conditions['logic'] ?? self::LOGIC_AND),
            'rules' => self::normalizedRules($conditions),
        ];
    }

    /**
     * @param  array<string, mixed>  $conditions
     * @return list<array{field: string, operator: string, value: mixed}>
     */
    protected static function normalizedRules(array $conditions): array
    {
        $rules = $conditions['rules'] ?? [];

        if (! is_array($rules)) {
            return [];
        }

        $normalized = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $field = (string) ($rule['field'] ?? '');

            if ($field === '') {
                continue;
            }

            $operator = self::normalizeOperator($rule['operator'] ?? 'equals');

            $normalized[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $rule['value'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array{field: string, operator: string, value: mixed}  $rule
     * @param  array<string, mixed>  $values
     */
    protected static function matchesRule(array $rule, array $values): bool
    {
        $field = $rule['field'];
        $operator = $rule['operator'];
        $expected = $rule['value'] ?? null;
        $actual = $values[$field] ?? null;

        return match ($operator) {
            'empty' => self::isEmptyValue($actual),
            'not_empty' => ! self::isEmptyValue($actual),
            'not_equals' => ! self::valuesEqual($actual, $expected),
            'contains' => self::valueContains($actual, $expected),
            default => self::valuesEqual($actual, $expected),
        };
    }

    /**
     * @param  list<bool>  $results
     */
    protected static function combine(array $results, string $logic): bool
    {
        if ($results === []) {
            return false;
        }

        if ($logic === self::LOGIC_OR) {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    protected static function normalizeAction(mixed $action): string
    {
        return $action === self::ACTION_HIDE ? self::ACTION_HIDE : self::ACTION_SHOW;
    }

    protected static function normalizeLogic(mixed $logic): string
    {
        return $logic === self::LOGIC_OR ? self::LOGIC_OR : self::LOGIC_AND;
    }

    protected static function normalizeOperator(mixed $operator): string
    {
        $operator = (string) $operator;

        return in_array($operator, self::OPERATORS, true) ? $operator : 'equals';
    }

    protected static function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_bool($value)) {
            return $value === false;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return blank($value);
    }

    protected static function valuesEqual(mixed $actual, mixed $expected): bool
    {
        if (is_bool($actual)) {
            return $actual === filter_var($expected, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (is_array($actual)) {
            return in_array($expected, $actual, true);
        }

        if (is_numeric($actual) && is_numeric($expected)) {
            return (string) $actual === (string) $expected;
        }

        return (string) $actual === (string) $expected;
    }

    protected static function valueContains(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($expected, $actual, true)
                || collect($actual)->contains(fn (mixed $item): bool => self::valuesEqual($item, $expected));
        }

        return self::valuesEqual($actual, $expected);
    }
}
