<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\Party;

/**
 * @implements CastsAttributes<Party|null, array<string, mixed>|Party|null>
 */
class PartyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Party
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Party) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return null;
        }

        return Party::fromArray($decoded);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Party) {
            return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
        }

        if (is_array($value)) {
            return json_encode(Party::fromArray($value)->toArray(), JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
