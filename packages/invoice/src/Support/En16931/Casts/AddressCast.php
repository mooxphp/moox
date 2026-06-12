<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\Address;

/**
 * @implements CastsAttributes<Address|null, array<string, mixed>|Address|null>
 */
class AddressCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Address
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Address) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return null;
        }

        return Address::fromArray($decoded);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Address) {
            return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
        }

        if (is_array($value)) {
            return json_encode(Address::fromArray($value)->toArray(), JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
