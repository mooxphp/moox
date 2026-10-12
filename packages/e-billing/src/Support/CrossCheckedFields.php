<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Address\Models\Address;
use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Models\EbillingDocument;

/**
 * The configured cross-checked fields (`e-billing.cross_checked`) and their master-data options,
 * scoped to the customer the document is attributed to (ADR 0004). Read-only: review never writes
 * master data.
 */
final class CrossCheckedFields
{
    /**
     * @return array{record: string, attribute?: string, addresses?: string}|null
     */
    public static function mapping(string $field): ?array
    {
        $mapping = config("e-billing.cross_checked.fields.{$field}");

        return is_array($mapping) && in_array($mapping['record'] ?? null, ['customer', 'company'], true) ? $mapping : null;
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
     * Scalar options of the attributed customer or company, value => value.
     *
     * @return array<string, string>
     */
    public static function options(string $field, ?EbillingDocument $document): array
    {
        $mapping = self::mapping($field);
        $attribute = $mapping['attribute'] ?? null;
        if ($mapping === null || ! is_string($attribute)) {
            return [];
        }

        $value = trim((string) (self::record($mapping['record'], $document)?->getAttribute($attribute) ?? ''));

        return $value === '' ? [] : [$value => $value];
    }

    /**
     * Addresses of the attributed company in the field's roles, keyed by address id, as the parts of
     * an invoice address (`line1`, `line2`, `postal_code`, `city`, `subdivision`, `country_code`).
     *
     * @return array<string, array{label: string, parts: array<string, ?string>}>
     */
    public static function addressOptions(string $field, ?EbillingDocument $document): array
    {
        $roles = self::mapping($field)['addresses'] ?? null;
        $company = self::record('company', $document);
        if (! is_string($roles) || ! $company instanceof Company) {
            return [];
        }

        $corroborator = new AttributionCorroborator;
        $roleFlags = $roles === 'delivery' ? $corroborator->deliveryAddressRoles() : $corroborator->buyerAddressRoles();

        $options = [];
        foreach ($corroborator->roleFilteredAddresses($company, $roleFlags) as $address) {
            $parts = self::addressParts($address);
            $options[(string) $address->getKey()] = [
                'label' => implode(', ', array_filter([$parts['line1'], $parts['line2'], trim($parts['postal_code'].' '.$parts['city']), $parts['country_code']])),
                'parts' => $parts,
            ];
        }

        return $options;
    }

    /**
     * Whether a document value is among the options, so a value the new customer does not know is flagged.
     * Null when there is nothing to compare: no value, no attributed customer, no mapping or no master-data value.
     *
     * @param  array<string, ?string>|string|null  $value  a scalar, or the parts of an address
     */
    public static function isKnown(string $field, mixed $value, ?EbillingDocument $document): ?bool
    {
        if (self::mapping($field) === null || $document?->customer_id === null) {
            return null;
        }

        if (self::isAddressField($field)) {
            if (! is_array($value) || array_filter($value) === []) {
                return null;
            }

            $options = self::addressOptions($field, $document);
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
        $options = self::options($field, $document);
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
     * @return array<string, ?string>
     */
    private static function addressParts(Address $address): array
    {
        return [
            'line1' => $address->street,
            'line2' => $address->street2,
            'postal_code' => $address->postal_code,
            'city' => $address->city,
            'subdivision' => $address->state,
            'country_code' => $address->country_code,
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

    /**
     * The attributed customer, also when soft-deleted: attribution keeps a deleted customer.
     */
    public static function findCustomer(?string $customerId): ?Customer
    {
        return $customerId !== null && $customerId !== '' ? Customer::query()->withTrashed()->find($customerId) : null;
    }

    private static function record(string $record, ?EbillingDocument $document): Customer|Company|null
    {
        if ($document === null) {
            return null;
        }

        return $record === 'customer'
            ? self::findCustomer($document->customer_id)
            : ($document->company_id !== null ? Company::query()->find($document->company_id) : null);
    }
}
