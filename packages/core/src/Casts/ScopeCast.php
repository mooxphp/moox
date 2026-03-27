<?php

namespace Moox\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Support\Scopes\ScopeValue;

class ScopeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ScopeValue
    {
        return ScopeValue::parse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof ScopeValue) {
            return (string) $value;
        }

        if (blank($value)) {
            return null;
        }

        return (string) ScopeValue::parse((string) $value);
    }
}
