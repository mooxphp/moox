<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Enums\InvoiceOriginRule;

/**
 * Classifies parsed bill data as domestic or foreign by the rules in `e-billing.foreign`.
 * A foreign document needs no e-invoice and ends ignored.
 */
final class ForeignInvoiceClassifier
{
    public const SOURCE_MAIL = 'mail';

    public const SOURCE_UPLOAD = 'upload';

    private const VAT_COUNTRY_PREFIXES = [
        'AT' => 'AT',
        'BE' => 'BE',
        'BG' => 'BG',
        'CY' => 'CY',
        'CZ' => 'CZ',
        'DE' => 'DE',
        'DK' => 'DK',
        'EE' => 'EE',
        'EL' => 'GR',
        'ES' => 'ES',
        'FI' => 'FI',
        'FR' => 'FR',
        'HR' => 'HR',
        'HU' => 'HU',
        'IE' => 'IE',
        'IT' => 'IT',
        'LT' => 'LT',
        'LU' => 'LU',
        'LV' => 'LV',
        'MT' => 'MT',
        'NL' => 'NL',
        'PL' => 'PL',
        'PT' => 'PT',
        'RO' => 'RO',
        'SE' => 'SE',
        'SI' => 'SI',
        'SK' => 'SK',
    ];

    /**
     * Whether documents from this intake source (`mail` or `upload`) are classified at all.
     */
    public function appliesTo(string $source): bool
    {
        return in_array($source, (array) config('e-billing.foreign.sources', [self::SOURCE_MAIL]), true);
    }

    /**
     * @param  array<string, mixed>  $billData
     * @return array{is_foreign: bool, country: ?string, matched_rule: InvoiceOriginRule}
     */
    public function classify(array $billData): array
    {
        $country = $this->resolveBillingCountry($billData);
        $vatAmount = $this->toNullableFloat($billData['vat_amount'] ?? null);

        if ($this->isDomestic($country)) {
            return ['is_foreign' => false, 'country' => $country, 'matched_rule' => InvoiceOriginRule::CountryDetectedDe];
        }

        if (config('e-billing.foreign.domestic_vat_id_makes_domestic', false) === true
            && $this->isDomestic($this->countryFromVatId($billData['customer_vat_id'] ?? null))) {
            return ['is_foreign' => false, 'country' => $country, 'matched_rule' => InvoiceOriginRule::DomesticVatId];
        }

        if ($country !== null) {
            if (config('e-billing.foreign.require_zero_vat', false) === true && $vatAmount !== null && $vatAmount !== 0.0) {
                return ['is_foreign' => false, 'country' => $country, 'matched_rule' => InvoiceOriginRule::DomesticVatCharged];
            }

            return ['is_foreign' => true, 'country' => $country, 'matched_rule' => InvoiceOriginRule::CountryDetected];
        }

        if (config('e-billing.foreign.net_only_without_country', true) === true
            && $this->isNetOnly($this->toNullableFloat($billData['gross_total'] ?? null), $vatAmount)) {
            return ['is_foreign' => true, 'country' => null, 'matched_rule' => InvoiceOriginRule::NetOnly];
        }

        return ['is_foreign' => false, 'country' => null, 'matched_rule' => InvoiceOriginRule::DefaultDe];
    }

    private function isDomestic(?string $country): bool
    {
        if ($country === null) {
            return false;
        }

        $domestic = array_map(
            static fn (mixed $code): string => strtoupper(trim((string) $code)),
            (array) config('e-billing.foreign.domestic_countries', ['DE']),
        );

        return in_array($country, $domestic, true);
    }

    /**
     * @param  array<string, mixed>  $billData
     */
    private function resolveBillingCountry(array $billData): ?string
    {
        $country = $this->normalizeCountry($billData['billing_country'] ?? null);
        if ($country !== null) {
            return $country;
        }

        $customerAddress = $billData['customer_address'] ?? null;
        if (is_array($customerAddress)) {
            $country = $this->normalizeCountry($customerAddress['country'] ?? null);
            if ($country !== null) {
                return $country;
            }
        }

        return $this->countryFromVatId($billData['customer_vat_id'] ?? null);
    }

    private function isNetOnly(?float $grossTotal, ?float $taxAmount): bool
    {
        $grossEmpty = $grossTotal === null || $grossTotal === 0.0;
        $taxEmpty = $taxAmount === null || $taxAmount === 0.0;

        return $grossEmpty && $taxEmpty;
    }

    private function normalizeCountry(mixed $country): ?string
    {
        if (! is_string($country) || trim($country) === '') {
            return null;
        }

        return strtoupper(trim($country));
    }

    private function countryFromVatId(mixed $vatId): ?string
    {
        if (! is_string($vatId)) {
            return null;
        }

        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $vatId));
        if (strlen($normalized) < 4) {
            return null;
        }

        return self::VAT_COUNTRY_PREFIXES[substr($normalized, 0, 2)] ?? null;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
