<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Moox\Address\Models\Address;
use Moox\Address\Support\AddressFingerprint;
use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\Invoice\Support\En16931\Address as InvoiceAddress;
use Moox\Invoice\Support\En16931\Party;

/**
 * Corroborates an attributed {@see Customer} against buyer name, VAT, country and address.
 *
 * Never clears or rewrites attribution — only produces field-validation verdicts.
 * No public seam — only {@see InvoiceFieldValidator} calls this.
 */
final class AttributionCorroborator
{
    /**
     * @return array{corroborates: bool, matched_id: string}
     */
    public function corroborateName(
        ?string $invoiceName,
        Customer $customer,
        ?Company $company,
    ): array {
        $invoiceTokens = $this->significantTokens($invoiceName ?? '');

        $candidates = $this->nameCandidates($customer, $company);

        foreach ($candidates as $candidate) {
            $candidateTokens = $this->significantTokens($candidate['name']);
            if ($invoiceTokens === [] || $candidateTokens === []) {
                continue;
            }

            if (array_intersect($invoiceTokens, $candidateTokens) !== []) {
                return [
                    'corroborates' => true,
                    'matched_id' => $candidate['id'],
                ];
            }
        }

        return [
            'corroborates' => false,
            'matched_id' => (string) $customer->getKey(),
        ];
    }

    /**
     * Corroborate a delivery/consignee party: name token overlap and address fingerprint
     * must both pass.
     *
     * @return array{corroborates: bool, matched_id: string}
     */
    public function corroborateDeliveryParty(
        Party $party,
        Customer $customer,
        ?Company $company,
    ): array {
        $invoiceName = is_string($party->name) && trim($party->name) !== '' ? $party->name : null;
        $nameResult = $this->corroborateName($invoiceName, $customer, $company);
        $addressResult = $this->findDeliveryMatchingAddress($party->address, $company);

        if ($addressResult === null) {
            return [
                'corroborates' => false,
                'matched_id' => $nameResult['matched_id'],
            ];
        }

        if ($nameResult['corroborates'] && $addressResult['exists']) {
            return [
                'corroborates' => true,
                'matched_id' => $addressResult['matched_id'],
            ];
        }

        $addressMatchedId = $addressResult['matched_id'];
        $fallbackId = $addressMatchedId !== '' ? $addressMatchedId : $nameResult['matched_id'];

        return [
            'corroborates' => false,
            'matched_id' => $addressResult['exists'] ? $addressMatchedId : $fallbackId,
        ];
    }

    /**
     * Compare VAT identifiers when both sides are present.
     *
     * @return bool|null true = agree, false = diverge, null = not comparable
     */
    public function compareVat(?string $invoiceVat, ?string $companyVat): ?bool
    {
        $left = VatIdNormalizer::normalize($invoiceVat);
        $right = VatIdNormalizer::normalize($companyVat);

        if ($left === null || $right === null) {
            return null;
        }

        return strcasecmp($left, $right) === 0;
    }

    /**
     * Compare country codes when both sides are present.
     *
     * Invoice country must appear on at least one role-filtered company address.
     *
     * @return bool|null true = agree, false = diverge, null = not comparable
     */
    public function compareCountry(?string $invoiceCountry, ?Company $company): ?bool
    {
        $invoice = $this->normalizeCountryCode($invoiceCountry);

        if ($invoice === null || $company === null) {
            return null;
        }

        $addresses = $this->roleFilteredAddresses($company, $this->buyerAddressRoles());

        if ($addresses->isEmpty()) {
            // No master-data country to compare — not a divergence (#25).
            return null;
        }

        $knownCountries = [];
        foreach ($addresses as $address) {
            $known = $this->normalizeCountryCode(
                is_string($address->country_code) ? $address->country_code : null,
            );
            if ($known !== null) {
                $knownCountries[$known] = true;
            }
        }

        if ($knownCountries === []) {
            return null;
        }

        return isset($knownCountries[$invoice]);
    }

    /**
     * Existence check of the parsed buyer address among buyer-role company addresses.
     *
     * @return array{exists: bool, matched_id: string}|null null when invoice address is empty
     */
    public function findMatchingAddress(?InvoiceAddress $invoiceAddress, ?Company $company): ?array
    {
        return $this->findAddressAmongRoleTiers(
            $invoiceAddress,
            $company,
            [$this->buyerAddressRoles()],
        );
    }

    /**
     * Existence check of a delivery address: delivery role first, then postal/billing fallback.
     *
     * @return array{exists: bool, matched_id: string}|null null when invoice address is empty
     */
    public function findDeliveryMatchingAddress(?InvoiceAddress $invoiceAddress, ?Company $company): ?array
    {
        $roles = $this->deliveryAddressRoles();

        return $this->findAddressAmongRoleTiers(
            $invoiceAddress,
            $company,
            [array_slice($roles, 0, 1), array_slice($roles, 1)],
        );
    }

    /**
     * @param  list<list<string>>  $roleTiers
     * @return array{exists: bool, matched_id: string}|null
     */
    private function findAddressAmongRoleTiers(
        ?InvoiceAddress $invoiceAddress,
        ?Company $company,
        array $roleTiers,
    ): ?array {
        if ($invoiceAddress === null || $invoiceAddress->isEmpty()) {
            return null;
        }

        if ($company === null) {
            return [
                'exists' => false,
                'matched_id' => '',
            ];
        }

        foreach ($roleTiers as $roles) {
            if ($roles === []) {
                continue;
            }

            $match = $this->findFingerprintAmongRoles($invoiceAddress, $company, $roles);
            if ($match !== null && $match['exists']) {
                return $match;
            }
        }

        return [
            'exists' => false,
            'matched_id' => (string) $company->getKey(),
        ];
    }

    /**
     * @return list<string>
     */
    public function significantTokens(string $name): array
    {
        $folded = Str::lower(Str::ascii($name));
        $stripped = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $folded) ?? '';
        $parts = preg_split('/\s+/u', trim($stripped), -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($parts) || $parts === []) {
            return [];
        }

        $minLength = (int) config('e-billing.corroboration.name_min_token_length', 4);
        $stopWords = $this->legalFormStopWords();

        $tokens = [];
        foreach ($parts as $part) {
            if (! is_string($part)) {
                continue;
            }
            if (mb_strlen($part) < $minLength) {
                continue;
            }
            if (isset($stopWords[$part])) {
                continue;
            }
            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @return list<array{name: string, id: string}>
     */
    private function nameCandidates(Customer $customer, ?Company $company): array
    {
        $candidates = [];

        $customerName = $customer->customer_name;
        if (is_string($customerName) && trim($customerName) !== '') {
            $candidates[] = [
                'name' => $customerName,
                'id' => (string) $customer->getKey(),
            ];
        }

        if ($company === null) {
            return $candidates;
        }

        foreach (['name', 'display_name', 'legal_name'] as $attribute) {
            $value = $company->getAttribute($attribute);
            if (! is_string($value) || trim($value) === '') {
                continue;
            }
            $candidates[] = [
                'name' => $value,
                'id' => (string) $company->getKey(),
            ];
        }

        return $candidates;
    }

    /**
     * @param  list<string>  $roles
     * @return array{exists: bool, matched_id: string}|null
     */
    private function findFingerprintAmongRoles(
        InvoiceAddress $invoiceAddress,
        Company $company,
        array $roles,
    ): ?array {
        if ($roles === []) {
            return null;
        }

        $invoiceFingerprint = AddressFingerprint::fromArray([
            'street' => $invoiceAddress->line1,
            'street2' => $invoiceAddress->line2,
            'postal_code' => $invoiceAddress->postal_code,
            'country_code' => $invoiceAddress->country_code,
        ]);

        foreach ($this->roleFilteredAddresses($company, $roles) as $address) {
            if (AddressFingerprint::fromAddress($address) === $invoiceFingerprint) {
                return [
                    'exists' => true,
                    'matched_id' => (string) $address->getKey(),
                ];
            }
        }

        return [
            'exists' => false,
            'matched_id' => '',
        ];
    }

    /**
     * @param  list<string>  $roles
     * @return Collection<int, Address>
     */
    private function roleFilteredAddresses(Company $company, array $roles): Collection
    {
        if ($roles === []) {
            return collect();
        }

        try {
            $company->loadMissing('addresses');
        } catch (\BadMethodCallException) {
            return collect();
        }

        if (! $company->relationLoaded('addresses')) {
            return collect();
        }

        /** @var Collection<int, Address> $addresses */
        $addresses = $company->getRelation('addresses');

        return $addresses->filter(function (Address $address) use ($roles): bool {
            foreach ($roles as $role) {
                if (! is_string($role) || $role === '') {
                    continue;
                }
                if ((bool) $address->pivot?->getAttribute($role)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * @return list<string>
     */
    private function buyerAddressRoles(): array
    {
        $roles = config('e-billing.corroboration.buyer_address_roles');
        if (is_array($roles) && $roles !== []) {
            return array_values(array_filter($roles, is_string(...)));
        }

        $legacy = config('e-billing.corroboration.address_roles');
        if (is_array($legacy) && $legacy !== []) {
            return array_values(array_filter($legacy, is_string(...)));
        }

        return ['billing_address', 'postal_address'];
    }

    /**
     * @return list<string>
     */
    private function deliveryAddressRoles(): array
    {
        $roles = config('e-billing.corroboration.delivery_address_roles');
        if (is_array($roles) && $roles !== []) {
            return array_values(array_filter($roles, is_string(...)));
        }

        return ['delivery_address', 'postal_address', 'billing_address'];
    }

    /**
     * @return array<string, true>
     */
    private function legalFormStopWords(): array
    {
        $configured = config('e-billing.corroboration.name_legal_form_stop_words', []);
        if (! is_array($configured)) {
            $configured = [];
        }

        $map = [];
        foreach ($configured as $word) {
            if (! is_string($word) || $word === '') {
                continue;
            }
            $map[Str::lower(Str::ascii($word))] = true;
        }

        return $map;
    }

    private function normalizeCountryCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = strtoupper(trim($value));

        return $trimmed === '' ? null : $trimmed;
    }

}
