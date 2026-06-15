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
            return true;
        }

        foreach ($locationRules as $andGroup) {
            if ($this->matchesAndGroup($andGroup, $context)) {
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
        foreach ($andGroup as $rule) {
            if (! $this->matchesRule($rule, $context)) {
                return false;
            }
        }

        return $andGroup !== [];
    }

    /**
     * @param  array{param: string, operator: string, value: mixed}  $rule
     */
    protected function matchesRule(array $rule, LocationContext $context): bool
    {
        $param = $rule['param'] ?? '';
        $operator = $rule['operator'] ?? '==';
        $value = $rule['value'] ?? null;

        if ($param !== 'entity') {
            if (app()->bound('log')) {
                Log::debug('Builder location rule skipped: unknown param.', ['param' => $param]);
            }

            return false;
        }

        return match ($operator) {
            '==' => (string) $context->entity === (string) $value,
            '!=' => (string) $context->entity !== (string) $value,
            default => false,
        };
    }
}
