<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Str;
use Moox\EBilling\Data\BuyerMasterData;
use Moox\EBilling\Data\BuyerMasterDataAddress;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\Invoice\Support\En16931\Address as InvoiceAddress;
use Moox\Invoice\Support\En16931\Party;

/**
 * Corroborates the matched {@see BuyerMasterData} against buyer name, VAT, country and address.
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
     */
    public function corroborateName(?string $invoiceName, BuyerMasterData $buyer): bool
    {
        $invoiceParts = $this->nameParts($invoiceName ?? '');
        $invoiceTokens = $this->significantTokensOf($invoiceParts);
        $invoiceFullName = $this->withoutLegalFormSuffix($invoiceParts);

        foreach ([$buyer->name, ...$buyer->alternativeNames] as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $candidateParts = $this->nameParts($candidate);
            $candidateTokens = $this->significantTokensOf($candidateParts);

            if ($invoiceTokens !== [] && $candidateTokens !== []) {
                $corroborates = array_intersect($invoiceTokens, $candidateTokens) !== [];
            } else {
                $corroborates = $invoiceFullName !== []
                    && $invoiceFullName === $this->withoutLegalFormSuffix($candidateParts);
            }

            if ($corroborates) {
                return true;
            }
        }

        return false;
    }

    /**
     * Corroborate a delivery/consignee party: name token overlap and address fingerprint
     * must both pass.
     *
     * @return array{corroborates: bool, matched_id: string, divergence?: AddressDivergence}
     */
    public function corroborateDeliveryParty(Party $party, BuyerMasterData $buyer): array
    {
        $invoiceName = trim($party->name) !== '' ? $party->name : null;
        $addressResult = $this->findDeliveryMatchingAddress($party->address, $buyer);

        if ($addressResult === null) {
            return [
                'corroborates' => false,
                'matched_id' => $buyer->party->id,
            ];
        }

        if ($this->corroborateName($invoiceName, $buyer) && $addressResult['exists']) {
            $corroborated = [
                'corroborates' => true,
                'matched_id' => $addressResult['matched_id'],
            ];
            if (isset($addressResult['divergence'])) {
                $corroborated['divergence'] = $addressResult['divergence'];
            }

            return $corroborated;
        }

        return [
            'corroborates' => false,
            'matched_id' => $addressResult['matched_id'],
        ];
    }

    /**
     * Compare VAT identifiers when both sides are present.
     *
     * @return bool|null true = agree, false = diverge, null = not comparable
     */
    public function compareVat(?string $invoiceVat, ?string $masterDataVat): ?bool
    {
        $left = VatIdNormalizer::normalize($invoiceVat);
        $right = VatIdNormalizer::normalize($masterDataVat);

        if ($left === null || $right === null) {
            return null;
        }

        return strcasecmp($left, $right) === 0;
    }

    /**
     * Compare country codes when both sides are present.
     *
     * Invoice country must appear on at least one billing address of the buyer.
     *
     * @return bool|null true = agree, false = diverge, null = not comparable
     */
    public function compareCountry(?string $invoiceCountry, BuyerMasterData $buyer): ?bool
    {
        $invoice = $this->normalizeCountryCode($invoiceCountry);

        if ($invoice === null) {
            return null;
        }

        $knownCountries = [];
        foreach ($buyer->billingAddresses as $address) {
            $known = $this->normalizeCountryCode($address->countryCode);
            if ($known !== null) {
                $knownCountries[$known] = true;
            }
        }

        if ($knownCountries === []) {
            // No master-data country to compare — not a divergence (#25).
            return null;
        }

        return isset($knownCountries[$invoice]);
    }

    /**
     * Existence check of the parsed buyer address among the buyer's billing addresses.
     *
     * @return AddressMatch|null null when invoice address is empty
     */
    public function findMatchingAddress(?InvoiceAddress $invoiceAddress, BuyerMasterData $buyer): ?array
    {
        return $this->findAddressAmongTiers($invoiceAddress, $buyer, [$buyer->billingAddresses]);
    }

    /**
     * Existence check of a delivery address: delivery addresses first, then the billing addresses.
     *
     * @return AddressMatch|null null when invoice address is empty
     */
    public function findDeliveryMatchingAddress(?InvoiceAddress $invoiceAddress, BuyerMasterData $buyer): ?array
    {
        return $this->findAddressAmongTiers(
            $invoiceAddress,
            $buyer,
            [$buyer->deliveryAddresses, $buyer->billingAddresses],
        );
    }

    /**
     * Existence check of one of the buyer's own sites: the delivery addresses only, no billing fallback.
     *
     * @return AddressMatch|null null when invoice address is empty
     */
    public function findOwnSiteDeliveryAddress(?InvoiceAddress $invoiceAddress, BuyerMasterData $buyer): ?array
    {
        return $this->findAddressAmongTiers($invoiceAddress, $buyer, [$buyer->deliveryAddresses]);
    }

    /**
     * A match without divergence in a later tier wins over an earlier tier's divergent one (#96).
     *
     * @param  list<list<BuyerMasterDataAddress>>  $tiers
     * @return AddressMatch|null
     */
    private function findAddressAmongTiers(
        ?InvoiceAddress $invoiceAddress,
        BuyerMasterData $buyer,
        array $tiers,
    ): ?array {
        if ($invoiceAddress === null || $invoiceAddress->isEmpty()) {
            return null;
        }

        $firstDivergentMatch = null;
        foreach ($tiers as $addresses) {
            $match = $this->findFingerprintAmong($invoiceAddress, $buyer, $addresses);
            if ($match === null) {
                continue;
            }

            if (! isset($match['divergence'])) {
                return $match;
            }

            $firstDivergentMatch ??= $match;
        }

        return $firstDivergentMatch ?? [
            'exists' => false,
            'matched_id' => $buyer->party->id,
        ];
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
     * Street, postal code and country must match exactly; the city must match word for word, or one city must
     * contain the other as whole words ("Town / Region" against "Town"); a city missing on either side is not
     * compared (#25). A city that only contains the other and differing additional address lines never block the
     * match: they are a master-data divergence, information only, returned in `divergence` (#96). An address
     * without divergence is preferred over the first one with. Null when no address matches.
     *
     * @param  list<BuyerMasterDataAddress>  $addresses
     * @return AddressMatch|null
     */
    private function findFingerprintAmong(
        InvoiceAddress $invoiceAddress,
        BuyerMasterData $buyer,
        array $addresses,
    ): ?array {
        $invoiceFingerprint = $this->postalFingerprint(
            $invoiceAddress->line1,
            $invoiceAddress->postal_code,
            $invoiceAddress->country_code,
        );
        $invoiceCityWords = $this->cityWords($invoiceAddress->city);
        $invoiceLines = $this->normalizedAdditionalLines($invoiceAddress->line2);

        $firstDivergentMatch = null;
        foreach ($addresses as $address) {
            $fingerprint = $this->postalFingerprint($address->line1, $address->postalCode, $address->countryCode);
            if ($fingerprint !== $invoiceFingerprint) {
                continue;
            }

            $divergence = [];

            $masterCityWords = $this->cityWords($address->city);
            if ($masterCityWords !== [] && $invoiceCityWords !== [] && $masterCityWords !== $invoiceCityWords) {
                if (! $this->containsWords($invoiceCityWords, $masterCityWords)
                    && ! $this->containsWords($masterCityWords, $invoiceCityWords)) {
                    continue;
                }
                $divergence['city'] = trim((string) $address->city);
            }

            if ($this->normalizedAdditionalLines($address->line2) !== $invoiceLines) {
                $divergence['additional_lines'] = implode(', ', $this->lines($address->line2, trim(...)));
            }

            $match = [
                'exists' => true,
                'matched_id' => $address->reference ?? $buyer->party->id,
            ];

            if ($divergence === []) {
                return $match;
            }

            $firstDivergentMatch ??= $match + ['divergence' => $divergence];
        }

        return $firstDivergentMatch;
    }

    /**
     * Street and postal code trimmed, country upper-cased; empty parts are null.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function postalFingerprint(?string $street, ?string $postalCode, ?string $countryCode): array
    {
        $trimmed = static fn (?string $value): ?string => trim((string) $value) !== '' ? trim((string) $value) : null;

        return [$trimmed($street), $trimmed($postalCode), $this->normalizeCountryCode($countryCode)];
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
