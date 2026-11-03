<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Customer\Models\Customer;
use Moox\EBilling\Data\BuyerLookup;
use Moox\EBilling\Data\BuyerMasterData;
use Moox\EBilling\Data\BuyerMasterDataAddress;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\BuyerMasterDataLookup;

/**
 * The configured cross-checked fields (`e-billing.cross_checked`) and their master-data options: what the buyer
 * master-data resolver returns for the customer the document is attributed to (ADR 0004, ADR 0020). Callers load
 * that buyer once with {@see buyerFor()} and pass it in. Read-only: review never writes master data.
 */
final class CrossCheckedFields
{
    /**
     * @return array{value?: string, addresses?: string}|null
     */
    public static function mapping(string $field): ?array
    {
        $mapping = config("e-billing.cross_checked.fields.{$field}");

        if (! is_array($mapping)) {
            return null;
        }

        return is_string($mapping['value'] ?? null) || is_string($mapping['addresses'] ?? null) ? $mapping : null;
    }

    public static function isAddressField(string $field): bool
    {
        return isset(self::mapping($field)['addresses']);
    }

    public static function isStrict(): bool
    {
        return (bool) config('e-billing.cross_checked.strict', false);
    }

    /**
     * The master data the resolver returns for the attributed customer, looked up by reference.
     */
    public static function buyerFor(?EbillingDocument $document): ?BuyerMasterData
    {
        if ($document?->customer_id === null) {
            return null;
        }

        return app(BuyerMasterDataLookup::class)
            ->resolve(new BuyerLookup(null, null, null, reference: $document->customer_id), $document)
            ->buyer;
    }

    /**
     * Scalar options of the buyer, value => value.
     *
     * @return array<string, string>
     */
    public static function options(string $field, ?BuyerMasterData $buyer): array
    {
        $masterDataValue = self::mapping($field)['value'] ?? null;
        if ($buyer === null || $masterDataValue === null) {
            return [];
        }

        $values = match ($masterDataValue) {
            'customer_number' => [$buyer->customerNumber],
            'name' => [$buyer->name, ...$buyer->alternativeNames],
            'vat_id' => [$buyer->vatId],
            default => [],
        };

        $options = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $options[$value] = $value;
            }
        }

        return $options;
    }

    /**
     * The buyer's addresses for the field, keyed by address reference, as the parts of an invoice address
     * (`line1`, `line2`, `postal_code`, `city`, `subdivision`, `country_code`). `buyer` fields offer the
     * billing addresses; `delivery` fields the delivery addresses, then the billing addresses.
     *
     * @return array<string, array{label: string, parts: array<string, ?string>}>
     */
    public static function addressOptions(string $field, ?BuyerMasterData $buyer): array
    {
        $kind = self::mapping($field)['addresses'] ?? null;
        if ($buyer === null || $kind === null) {
            return [];
        }

        $addresses = $kind === 'delivery'
            ? [...$buyer->deliveryAddresses, ...$buyer->billingAddresses]
            : $buyer->billingAddresses;

        $options = [];
        foreach ($addresses as $index => $address) {
            $parts = self::addressParts($address);
            $label = array_filter([
                $parts['line1'],
                $parts['line2'],
                trim($parts['postal_code'].' '.$parts['city']),
                $parts['country_code'],
            ]);
            $options[$address->reference ?? (string) $index] ??= ['label' => implode(', ', $label), 'parts' => $parts];
        }

        return $options;
    }

    /**
     * Whether a document value is among the buyer's options, so a value the new customer does not know is flagged.
     * Null when there is nothing to compare: no value, no buyer, no mapping or no master-data value.
     *
     * @param  array<string, ?string>|string|null  $value  a scalar, or the parts of an address
     */
    public static function isKnown(string $field, mixed $value, ?BuyerMasterData $buyer): ?bool
    {
        if (self::mapping($field) === null || $buyer === null) {
            return null;
        }

        if (self::isAddressField($field)) {
            if (! is_array($value) || array_filter($value) === []) {
                return null;
            }

            $options = self::addressOptions($field, $buyer);
            if ($options === []) {
                return null;
            }

            $fingerprint = self::fingerprint($value);
            foreach ($options as $option) {
                if (self::fingerprint($option['parts']) === $fingerprint) {
                    return true;
                }
            }

            return false;
        }

        $value = trim((string) $value);
        $options = self::options($field, $buyer);
        if ($value === '' || $options === []) {
            return null;
        }

        foreach ($options as $option) {
            if (strcasecmp($option, $value) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * The attributed customer, also when soft-deleted: attribution keeps a deleted customer.
     */
    public static function findCustomer(?string $customerId): ?Customer
    {
        return $customerId !== null && $customerId !== '' ? Customer::query()->withTrashed()->find($customerId) : null;
    }

    /**
     * @return array<string, ?string>
     */
    private static function addressParts(BuyerMasterDataAddress $address): array
    {
        return [
            'line1' => $address->line1,
            'line2' => $address->line2,
            'postal_code' => $address->postalCode,
            'city' => $address->city,
            'subdivision' => $address->subdivision,
            'country_code' => $address->countryCode,
        ];
    }

    /**
     * @param  array<string, ?string>  $parts
     */
    private static function fingerprint(array $parts): string
    {
        return implode('|', array_map(
            static fn (string $key): string => mb_strtolower(trim((string) ($parts[$key] ?? ''))),
            ['line1', 'line2', 'postal_code', 'city', 'country_code'],
        ));
    }
}
