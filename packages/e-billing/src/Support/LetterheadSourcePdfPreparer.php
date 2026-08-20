<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Services\LetterheadUnderlayService;
use RuntimeException;

final class LetterheadSourcePdfPreparer implements SourcePdfPreparerInterface
{
    public function __construct(
        private readonly LetterheadUnderlayService $letterheadUnderlayService,
    ) {
    }

    public function prepare(EbillingDocument $document): string
    {
        $source = $document->source;

        if (! $source instanceof UploadedPdfSource || ! $source->requires_letterhead_overlay) {
            return $document->sourceFullPath();
        }

        $profile = $this->resolveLetterheadProfile($document);
        $pdfPath = is_array($profile) ? ($profile['pdf_path'] ?? '') : '';

        if (! is_string($pdfPath) || $pdfPath === '') {
            throw new RuntimeException(
                'Letterhead overlay is required but e-billing.letterhead.default.pdf_path is empty.'
            );
        }

        if (! str_starts_with($pdfPath, DIRECTORY_SEPARATOR)) {
            $pdfPath = base_path($pdfPath);
        }

        if (! is_file($pdfPath)) {
            throw new RuntimeException("Letterhead PDF not found at [{$pdfPath}].");
        }

        $offsetXMm = is_numeric($profile['offset_x_mm'] ?? null) ? (float) $profile['offset_x_mm'] : 0.0;
        $offsetYMm = is_numeric($profile['offset_y_mm'] ?? null) ? (float) $profile['offset_y_mm'] : 0.0;

        $binary = $this->letterheadUnderlayService->compose(
            $document->sourceFullPath(),
            $pdfPath,
            $offsetXMm,
            $offsetYMm,
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'ebilling-underlay-');
        if ($tempPath === false) {
            throw new RuntimeException('Could not allocate temporary file for underlay PDF.');
        }

        file_put_contents($tempPath, $binary);

        return $tempPath;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLetterheadProfile(EbillingDocument $document): array
    {
        $default = config('e-billing.letterhead.default');
        $fallback = is_array($default) ? $default : [];

        $sellerKey = $this->resolveSellerKey($document);
        if ($sellerKey === null) {
            return $fallback;
        }

        $profiles = config('e-billing.letterhead.by_seller', []);
        if (! is_array($profiles)) {
            return $fallback;
        }

        $profile = $profiles[$sellerKey] ?? null;

        return is_array($profile) ? array_replace($fallback, $profile) : $fallback;
    }

    private function resolveSellerKey(EbillingDocument $document): ?string
    {
        $billData = $document->bill_data;
        if (! is_array($billData)) {
            return null;
        }

        $sellerKey = $billData['supplier_number'] ?? null;

        return is_string($sellerKey) && $sellerKey !== '' ? $sellerKey : null;
    }
}
