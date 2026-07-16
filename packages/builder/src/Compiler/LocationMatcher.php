<?php

declare(strict_types=1);

namespace Moox\Builder\Compiler;

use Illuminate\Support\Facades\Log;
use Moox\Builder\Data\LocationContext;

class LocationMatcher
{
    /**
     * @param  list<list<array{param: string, operator: string, value: mixed}>>  $locationRules
     */
    public function matches(array $locationRules, LocationContext $context): bool
    {
        if ($locationRules === []) {
            return false;
        }

        foreach ($locationRules as $andGroup) {
            if ($this->matchesAndGroup($andGroup, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Entity-level prefilter for cached definition loading (no record yet).
     *
     * @param  list<list<array{param: string, operator: string, value: mixed}>>  $locationRules
     */
    public function matchesEntityScope(array $locationRules, string $entity): bool
    {
        if ($locationRules === []) {
            return false;
        }

        foreach ($locationRules as $andGroup) {
            if ($this->matchesEntityAndGroup($andGroup, $entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{param: string, operator: string, value: mixed}>  $andGroup
     */
    protected function matchesAndGroup(array $andGroup, LocationContext $context): bool
    {
        if ($andGroup === []) {
            return false;
        }

        foreach ($andGroup as $rule) {
            if (! $this->matchesRule($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{param: string, operator: string, value: mixed}>  $andGroup
     */
    protected function matchesEntityAndGroup(array $andGroup, string $entity): bool
    {
        if ($andGroup === []) {
            return false;
        }

        $hasEntityRule = false;

        foreach ($andGroup as $rule) {
            if (($rule['param'] ?? '') !== 'entity') {
                continue;
            }

            $hasEntityRule = true;

            if (! $this->compare((string) $entity, (string) ($rule['value'] ?? ''), (string) ($rule['operator'] ?? '=='))) {
                return false;
            }
        }

        return $hasEntityRule;
    }

    /**
     * @param  array{param: string, operator: string, value: mixed}  $rule
     */
    protected function matchesRule(array $rule, LocationContext $context): bool
    {
        $param = (string) ($rule['param'] ?? '');
        $operator = (string) ($rule['operator'] ?? '==');
        $expected = $rule['value'] ?? null;

        if ($param === 'entity') {
            return $this->compare((string) $context->entity, (string) $expected, $operator);
        }

        if ($this->requiresRecord($param) && ! $context->hasRecord()) {
            return true;
        }

        $actual = $this->resolveActualValue($param, $context);

        if ($actual === null && $this->requiresRecord($param)) {
            // Parameter is not available on this record (e.g. items have no
            // `type`, so `record_type` cannot be evaluated). Treat the rule as
            // non-applicable instead of excluding the entity.
            return true;
        }

        return match ($param) {
            'record_type', 'record_status', 'user_role' => $this->compare($actual, $expected, $operator),
            default => $this->matchesDynamicParam($param, $actual, $expected, $operator),
        };
    }

    protected function requiresRecord(string $param): bool
    {
        return in_array($param, ['record_type', 'record_status'], true)
            || str_starts_with($param, 'taxonomy:');
    }

    protected function resolveActualValue(string $param, LocationContext $context): mixed
    {
        if ($param === 'entity') {
            return $context->entity;
        }

        if (array_key_exists($param, $context->params)) {
            return $context->get($param);
        }

        if (str_starts_with($param, 'taxonomy:')) {
            return $context->get($param);
        }

        if ($param === 'record_type') {
            return $context->get('record_type');
        }

        if ($param === 'record_status') {
            return $context->get('record_status');
        }

        if ($param === 'user_role') {
            return $context->get('user_role');
        }

        return null;
    }

    protected function contextHas(LocationContext $context, string $param): bool
    {
        return array_key_exists($param, $context->params);
    }

    protected function matchesDynamicParam(string $param, mixed $actual, mixed $expected, string $operator): bool
    {
        if (str_starts_with($param, 'taxonomy:')) {
            return $this->compareList($actual, $expected, $operator);
        }

        if (app()->bound('log')) {
            Log::debug('Builder location rule skipped: unknown param.', ['param' => $param]);
        }

        return false;
    }

    protected function compare(mixed $actual, mixed $expected, string $operator): bool
    {
        if (is_array($actual)) {
            return $this->compareList($actual, $expected, $operator);
        }

        return match ($operator) {
            '==' => (string) $actual === (string) $expected,
            '!=' => (string) $actual !== (string) $expected,
            'in' => $this->valueInList($expected, $actual),
            'not in' => ! $this->valueInList($expected, $actual),
            default => false,
        };
    }

    protected function compareList(mixed $actual, mixed $expected, string $operator): bool
    {
        $actualValues = $this->normalizeList($actual);
        $expectedValues = $this->normalizeList($expected);

        if ($actualValues === []) {
            return match ($operator) {
                '!=', 'not in' => true,
                default => false,
            };
        }

        return match ($operator) {
            '==' => count($expectedValues) === 1 && $this->listsIntersect($actualValues, $expectedValues),
            '!=' => count($expectedValues) === 1 && ! $this->listsIntersect($actualValues, $expectedValues),
            'in' => $this->listsIntersect($actualValues, $expectedValues),
            'not in' => ! $this->listsIntersect($actualValues, $expectedValues),
            default => false,
        };
    }

    /**
     * @return list<int|string>
     */
    protected function normalizeList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== ''));
        }

        if (is_string($value) && str_contains($value, ',')) {
            return array_values(array_filter(array_map(trim(...), explode(',', $value))));
        }

        return [$value];
    }

    /**
     * @param  list<int|string>  $list
     */
    protected function valueInList(mixed $list, mixed $needle): bool
    {
        return in_array($needle, $this->normalizeList($list), true);
    }

    /**
     * @param  list<int|string>  $left
     * @param  list<int|string>  $right
     */
    protected function listsIntersect(array $left, array $right): bool
    {
        foreach ($left as $value) {
            foreach ($right as $expected) {
                if ((string) $value === (string) $expected) {
                    return true;
                }
            }
        }

        return false;
    }
}
