<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\Data\Models\StaticPaymentMean;
use Moox\Data\Models\StaticVatCategory;
use Moox\EBilling\Exceptions\CodelistNotImportedException;
use Moox\EBilling\Exceptions\UnresolvedCodelistLabelException;

final class ConfiguredEn16931CodeResolver
{
    public function paymentMeansCodeFromConfig(): string
    {
        return $this->assertCode(
            (string) config('e-billing.payment_means_code', '58'),
            'payment_means_code',
            StaticPaymentMean::class,
            'static_payment_means',
        );
    }

    public function vatCategoryCodeFromConfig(): string
    {
        return $this->assertCode(
            (string) config('e-billing.vat_category_code', 'S'),
            'vat_category_code',
            StaticVatCategory::class,
            'static_vat_categories',
        );
    }

    public function resolvePaymentMeansCode(?string $override): string
    {
        $trimmed = trim((string) $override);

        if ($trimmed !== '') {
            return $this->assertPaymentMeansCode($trimmed);
        }

        return $this->paymentMeansCodeFromConfig();
    }

    public function assertPaymentMeansCode(string $code, string $codelist = 'payment_means_code'): string
    {
        return $this->assertCode($code, $codelist, StaticPaymentMean::class, 'static_payment_means');
    }

    public function assertVatCategoryCode(string $code, string $codelist = 'vat_category_code'): string
    {
        return $this->assertCode($code, $codelist, StaticVatCategory::class, 'static_vat_categories');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function assertCode(string $code, string $codelist, string $modelClass, string $table): string
    {
        $code = trim($code);

        if ($code === '') {
            throw new UnresolvedCodelistLabelException($codelist, $code);
        }

        if ($modelClass::query()->where('code', $code)->exists()) {
            return $code;
        }

        if ($modelClass::query()->doesntExist()) {
            throw new CodelistNotImportedException($table);
        }

        throw new UnresolvedCodelistLabelException($codelist, $code);
    }
}
