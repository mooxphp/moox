<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\StoreBillDataJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\StoredRelativePath;

final class CreateManualUploadDocumentAction
{
    /**
     * @param  array{
     *     source_pdf_path: string,
     *     source_pdf_disk?: string,
     *     original_filename?: ?string,
     *     scope?: ?string,
     *     requires_letterhead_overlay?: bool
     * }  $data
     */
    public function execute(array $data): EbillingDocument
    {
        $configuredDisk = (string) config('e-billing.manual_upload.source_disk', 'local');
        $directory = (string) config('e-billing.manual_upload.source_path', 'ebilling/manual-uploads/source');
        $requestedDisk = $data['source_pdf_disk'] ?? $configuredDisk;

        if (! is_string($requestedDisk) || $requestedDisk !== $configuredDisk) {
            throw new InvalidArgumentException('Manual upload disk does not match configuration.');
        }

        $path = StoredRelativePath::assertUnderDirectory($data['source_pdf_path'], $directory);
        $scope = $data['scope'] ?? 'credit-notes';

        $source = UploadedPdfSource::query()->create([
            'source_pdf_disk' => $configuredDisk,
            'source_pdf_path' => $path,
            'original_filename' => $data['original_filename'] ?? null,
            'scope' => $scope,
            'requires_letterhead_overlay' => (bool) ($data['requires_letterhead_overlay'] ?? false),
        ]);

        $document = EbillingDocument::query()->create([
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'scope' => $scope,
            'gateway_status' => null,
            'review_status' => InvoiceProcessingStatus::ParserCreated,
        ]);

        StoreBillDataJob::dispatch($document->getKey());

        return $document;
    }
}
