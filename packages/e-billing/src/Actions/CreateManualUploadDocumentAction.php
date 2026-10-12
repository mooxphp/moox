<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\StoreBillDataJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\DocumentClassification;
use Moox\EBilling\Support\FieldValidationProfile;
use Moox\EBilling\Support\IdenticalDuplicateNotifier;
use Moox\EBilling\Support\StoredRelativePath;

final class CreateManualUploadDocumentAction
{
    /**
     * @param  array{
     *     source_pdf_path: string,
     *     source_pdf_disk?: string,
     *     original_filename?: ?string,
     *     scope?: ?string,
     *     requires_letterhead_overlay?: bool,
     *     resource?: ?string,
     *     document_type?: string|int|null,
     *     recipient_email?: ?string,
     *     uploader_user_id?: ?string
     * }  $data  `resource` is the e-billing resource config key; `document_type` the declared BT-3 code
     */
    public function execute(array $data): EbillingDocument
    {
        $declaredType = $this->declaredDocumentType($data);

        $configuredDisk = (string) config('e-billing.manual_upload.source_disk', 'local');
        $directory = (string) config('e-billing.manual_upload.source_path', 'ebilling/manual-uploads/source');
        $requestedDisk = $data['source_pdf_disk'] ?? $configuredDisk;

        if (! is_string($requestedDisk) || $requestedDisk !== $configuredDisk) {
            throw new InvalidArgumentException('Manual upload disk does not match configuration.');
        }

        $path = StoredRelativePath::assertUnderDirectory($data['source_pdf_path'], $directory);
        $this->assertWithinMaxSize($configuredDisk, $path);
        $scope = $data['scope'] ?? 'credit-notes';

        $recipientEmail = $this->normalizeRecipientEmail($data['recipient_email'] ?? null);
        $this->assertRecipientEmailForUpload($declaredType, $recipientEmail);

        $uploaderUserId = $data['uploader_user_id'] ?? null;
        $uploaderUserId = $uploaderUserId === null || $uploaderUserId === '' ? null : (string) $uploaderUserId;

        $source = UploadedPdfSource::query()->create([
            'source_pdf_disk' => $configuredDisk,
            'source_pdf_path' => $path,
            'original_filename' => $data['original_filename'] ?? null,
            'scope' => $scope,
            'requires_letterhead_overlay' => (bool) ($data['requires_letterhead_overlay'] ?? false),
            'document_type' => $declaredType,
            'recipient_email' => $recipientEmail,
            'uploader_user_id' => $uploaderUserId,
        ]);

        $document = EbillingDocument::query()->create([
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'scope' => $scope,
            'gateway_status' => null,
            'review_status' => InvoiceProcessingStatus::ParserCreated,
        ]);

        if ($declaredType !== null) {
            DocumentClassification::recordActivity($document, null, $declaredType, false, 'upload');
        }

        app(IdenticalDuplicateNotifier::class)->rememberCurrentUser($document);

        StoreBillDataJob::dispatch($document->getKey());

        return $document;
    }

    /**
     * The declared type must be one of the resource's selectable codes; a single code is declared implicitly.
     *
     * @param  array<string, mixed>  $data
     */
    private function declaredDocumentType(array $data): ?string
    {
        $resource = $data['resource'] ?? null;
        $selectable = is_string($resource) && $resource !== ''
            ? DocumentClassification::selectableForUpload($resource)
            : [];

        if ($selectable === []) {
            return null;
        }

        if (count($selectable) === 1) {
            return $selectable[0];
        }

        // A select with numeric option keys hands the code back as an int.
        $declared = $data['document_type'] ?? null;
        $declared = is_int($declared) || is_string($declared) ? (string) $declared : null;

        if ($declared === null || ! in_array($declared, $selectable, true)) {
            throw new InvalidArgumentException(
                'Choose the document type of the upload: one of '.implode(', ', $selectable).'.',
            );
        }

        return $declared;
    }

    private function normalizeRecipientEmail(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assertRecipientEmailForUpload(?string $declaredType, ?string $recipientEmail): void
    {
        if ($recipientEmail !== null && ! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Recipient email is invalid.');
        }

        if ($declaredType === null) {
            return;
        }

        if (
            $recipientEmail === null
            && FieldValidationProfile::priority('buyer_email', $declaredType) === 'must'
        ) {
            throw new InvalidArgumentException('Recipient email is required for this document type.');
        }
    }

    private function assertWithinMaxSize(string $disk, string $path): void
    {
        $maxSizeKb = max(1, (int) config('e-billing.manual_upload.max_size_kb', 20480));

        if (! Storage::disk($disk)->exists($path)) {
            return;
        }

        if (Storage::disk($disk)->size($path) > $maxSizeKb * 1024) {
            throw new InvalidArgumentException('Manual upload exceeds the maximum file size.');
        }
    }
}
