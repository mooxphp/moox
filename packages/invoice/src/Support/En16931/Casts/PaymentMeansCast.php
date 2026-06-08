<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\PaymentMeans;

/**
 * @implements CastsAttributes<PaymentMeans|null, array<string, mixed>|PaymentMeans|null>
 */
class PaymentMeansCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PaymentMeans
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PaymentMeans) {
            return $value;
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($decoded)) {
            return null;
        }

        return PaymentMeans::fromArray($decoded);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PaymentMeans) {
            return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
        }

        if (is_array($value)) {
            return json_encode(PaymentMeans::fromArray($value)->toArray(), JSON_THROW_ON_ERROR);
        }

        return null;
    }
}
