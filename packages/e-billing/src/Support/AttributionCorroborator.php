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
 *
 * @phpstan-type AddressDivergence array{city?: string, additional_lines?: string}
 * @phpstan-type AddressMatch array{exists: bool, matched_id: string, divergence?: AddressDivergence}
 */
final class AttributionCorroborator
{
    /**
     * Names corroborate when they share a significant token. When either side has none ("ABC GmbH": every part is
     * short or a legal form), the full names must be equal instead, short parts included and a trailing legal form
     * ignored (#108).
     *
     * @return array{corroborates: bool, matched_id: string}
     */
    public function corroborateName(
        ?string $invoiceName,
        Customer $customer,
        ?Company $company,
    ): array {
        $invoiceParts = $this->nameParts($invoiceName ?? '');
        $invoiceTokens = $this->significantTokensOf($invoiceParts);
        $invoiceFullName = $this->withoutLegalFormSuffix($invoiceParts);

        $candidates = $this->nameCandidates($customer, $company);

        foreach ($candidates as $candidate) {
            $candidateParts = $this->nameParts($candidate['name']);
            $candidateTokens = $this->significantTokensOf($candidateParts);

            if ($invoiceTokens !== [] && $candidateTokens !== []) {
                $corroborates = array_intersect($invoiceTokens, $candidateTokens) !== [];
            } else {
                $corroborates = $invoiceFullName !== []
                    && $invoiceFullName === $this->withoutLegalFormSuffix($candidateParts);
            }

            if ($corroborates) {
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
     * @return array{corroborates: bool, matched_id: string, divergence?: AddressDivergence}
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
            $corroborated = [
                'corroborates' => true,
                'matched_id' => $addressResult['matched_id'],
            ];
            if (isset($addressResult['divergence'])) {
                $corroborated['divergence'] = $addressResult['divergence'];
            }

            return $corroborated;
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
     * @return AddressMatch|null null when invoice address is empty
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
     * @return AddressMatch|null null when invoice address is empty
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
     * Existence check of one of the buyer's own sites: the delivery role only, no postal/billing fallback.
     *
     * @return AddressMatch|null null when invoice address is empty
     */
    public function findOwnSiteDeliveryAddress(?InvoiceAddress $invoiceAddress, ?Company $company): ?array
    {
        return $this->findAddressAmongRoleTiers(
            $invoiceAddress,
            $company,
            [array_slice($this->deliveryAddressRoles(), 0, 1)],
        );
    }

    /**
     * A match without divergence in a later tier wins over an earlier tier's divergent one (#96).
     *
     * @param  list<list<string>>  $roleTiers
     * @return AddressMatch|null
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

        $firstDivergentMatch = null;
        foreach ($roleTiers as $roles) {
            if ($roles === []) {
                continue;
            }

            $match = $this->findFingerprintAmongRoles($invoiceAddress, $company, $roles);
            if ($match === null || ! $match['exists']) {
                continue;
            }

            if (! isset($match['divergence'])) {
                return $match;
            }

            $firstDivergentMatch ??= $match;
        }

        return $firstDivergentMatch ?? [
            'exists' => false,
            'matched_id' => (string) $company->getKey(),
        ];
    }

    /**
     * @return list<string>
     */
    public function significantTokens(string $name): array
    {
        return $this->significantTokensOf($this->nameParts($name));
    }

    /**
     * The lowercased ASCII words of a name in printed order, without punctuation.
     *
     * @return list<string>
     */
    private function nameParts(string $name): array
    {
        $folded = Str::lower(Str::ascii($name));
        $stripped = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $folded) ?? '';

        return preg_split('/\s+/u', trim($stripped), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  list<string>  $parts
     * @return list<string>
     */
    private function significantTokensOf(array $parts): array
    {
        $minLength = (int) config('e-billing.corroboration.name_min_token_length', 4);
        $stopWords = $this->legalFormStopWords();

        return array_values(array_unique(array_filter(
            $parts,
            fn (string $part): bool => mb_strlen($part) >= $minLength && ! isset($stopWords[$part]),
        )));
    }

    /**
     * The parts without the trailing legal-form stop words ("abc gmbh co kg" → "abc"), keeping at least the first
     * part: a short name may itself be a stop word ("AB GmbH").
     *
     * @param  list<string>  $parts
     * @return list<string>
     */
    private function withoutLegalFormSuffix(array $parts): array
    {
        $stopWords = $this->legalFormStopWords();
        while (count($parts) > 1 && isset($stopWords[$parts[count($parts) - 1]])) {
            array_pop($parts);
        }

        return $parts;
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
     * Street, postal code and country must match exactly; the city must match word for word, or one city must
     * contain the other as whole words ("Town / Region" against "Town"); a city missing on either side is not
     * compared (#25). A city that only contains the other and differing additional address lines never block the
     * match: they are a master-data divergence, information only, returned in `divergence` (#96). An address
     * without divergence is preferred over the first one with.
     *
     * @param  list<string>  $roles
     * @return AddressMatch|null
     */
    private function findFingerprintAmongRoles(
        InvoiceAddress $invoiceAddress,
        Company $company,
        array $roles,
    ): ?array {
        if ($roles === []) {
            return null;
        }

        $invoiceFingerprint = $this->postalFingerprint(AddressFingerprint::fromArray([
            'street' => $invoiceAddress->line1,
            'postal_code' => $invoiceAddress->postal_code,
            'country_code' => $invoiceAddress->country_code,
        ]));
        $invoiceCityWords = $this->cityWords($invoiceAddress->city);
        $invoiceLines = $this->normalizedAdditionalLines($invoiceAddress->line2);

        $firstDivergentMatch = null;
        foreach ($this->roleFilteredAddresses($company, $roles) as $address) {
            $fingerprint = AddressFingerprint::fromAddress($address);
            if ($this->postalFingerprint($fingerprint) !== $invoiceFingerprint) {
                continue;
            }

            $divergence = [];

            $masterCityWords = $this->cityWords($address->city);
            if ($masterCityWords !== [] && $invoiceCityWords !== [] && $masterCityWords !== $invoiceCityWords) {
                if (! $this->containsWords($invoiceCityWords, $masterCityWords)
                    && ! $this->containsWords($masterCityWords, $invoiceCityWords)) {
                    continue;
                }
                $divergence['city'] = trim($address->city);
            }

            if ($this->normalizedAdditionalLines($fingerprint['street2']) !== $invoiceLines) {
                $divergence['additional_lines'] = implode(', ', $this->lines($fingerprint['street2'], trim(...)));
            }

            $match = [
                'exists' => true,
                'matched_id' => (string) $address->getKey(),
            ];

            if ($divergence === []) {
                return $match;
            }

            $firstDivergentMatch ??= $match + ['divergence' => $divergence];
        }

        return $firstDivergentMatch ?? [
            'exists' => false,
            'matched_id' => '',
        ];
    }

    /**
     * @return list<string>
     */
    private function cityWords(?string $value): array
    {
        return preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($value ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Whether `$needle` occurs in `$haystack` as a contiguous run of whole words.
     *
     * @param  list<string>  $haystack
     * @param  list<string>  $needle
     */
    private function containsWords(array $haystack, array $needle): bool
    {
        $length = count($needle);
        if ($length === 0) {
            return false;
        }

        for ($offset = 0; $offset + $length <= count($haystack); $offset++) {
            if (array_slice($haystack, $offset, $length) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, ?string>  $fingerprint
     * @return array<string, ?string>
     */
    private function postalFingerprint(array $fingerprint): array
    {
        unset($fingerprint['street2']);

        return $fingerprint;
    }

    /**
     * Additional lines compared case-, whitespace- and order-insensitively, ignoring surrounding punctuation and
     * symbols such as the dashes in "-Accounts Payable-".
     *
     * @return list<string>
     */
    private function normalizedAdditionalLines(?string $lines): array
    {
        $normalized = $this->lines($lines, $this->normalizedLine(...));
        sort($normalized);

        return $normalized;
    }

    /**
     * The non-empty lines of a multi-line value, each passed through `$transform`.
     *
     * @param  callable(string): string  $transform
     * @return list<string>
     */
    private function lines(?string $value, callable $transform): array
    {
        return array_values(array_filter(
            array_map($transform, preg_split('/\R/u', $value ?? '') ?: []),
            static fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * Lower-cased, whitespace collapsed, surrounding punctuation and symbols dropped; '' when empty.
     */
    private function normalizedLine(?string $line): string
    {
        $line = preg_replace('/^[\p{P}\p{S}\s]+|[\p{P}\p{S}\s]+$/u', '', mb_strtolower($line ?? '')) ?? '';

        return preg_replace('/\s+/u', ' ', $line) ?? '';
    }

    /**
     * @param  list<string>  $roles
     * @return Collection<int, Address>
     */
    public function roleFilteredAddresses(Company $company, array $roles): Collection
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
    public function buyerAddressRoles(): array
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
    public function deliveryAddressRoles(): array
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
