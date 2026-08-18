<?php

declare(strict_types=1);

namespace Moox\Invoice\Support\En16931\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Moox\Invoice\Support\En16931\Party;

/**
 * Persist delivery as a party (name + address) without VAT, tax number, or contact.
 *
 * @implements CastsAttributes<Party|null, array<string, mixed>|Party|null>
 */
class DeliveryPartyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Party
    {
        return Party::deliveryFromCastValue($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Party::deliveryToJson($value);
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function migrateStoredArray(?array $decoded): ?array
    {
        return Party::migrateDeliveryStored($decoded);
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     * @return array<string, mixed>|null
     */
    public static function unwrapStoredArray(?array $decoded): ?array
    {
        return Party::unwrapDeliveryStored($decoded);
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    public static function isLegacyAddressShape(array $decoded): bool
    {
        return Party::isLegacyDeliveryAddressShape($decoded);
    }
}
