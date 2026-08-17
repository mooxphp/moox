<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Moox\EBilling\Adapters\ZugferdInvoiceAdapter;
use Moox\EBilling\Contracts\PdfaNormalizerInterface;
use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ArtifactGenerated;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Contracts\HybridArtifactGeneratorStrategyInterface;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\CopyPdfComposer;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Services\ParsedInvoiceMapper;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Throwable;

class GenerateArtifactJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300];

    public function __construct(
        public string $ebillingDocumentId,
    ) {
    }

    public function handle(
        FormatRegistry $formatRegistry,
        EBillingFormatResolver $formatResolver,
        ParsedInvoiceMapper $parsedInvoiceMapper,
        InvoiceFieldValidator $invoiceFieldValidator,
        SourcePdfPreparerInterface $sourcePdfPreparer,
        PdfaNormalizerInterface $pdfaNormalizer,
        CopyPdfComposer $copyPdfComposer,
    ): void {
        $this->setProgress(0);

        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument) {
            Log::warning('[EBilling] GenerateArtifactJob: document not found', [
                'ebilling_document_id' => $this->ebillingDocumentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $attachment = $document->inboxAttachment();

        $retryableStatuses = [
            EBillingAttachmentProcessingStatus::Generating,
            EBillingAttachmentProcessingStatus::GenerationFailed,
            EBillingAttachmentProcessingStatus::Validating,
            EBillingAttachmentProcessingStatus::ValidationFailed,
            EBillingAttachmentProcessingStatus::ValidatorError,
        ];

        $canRun = in_array($document->gateway_status, $retryableStatuses, true);

        if ($attachment !== null) {
            $canRun = $attachment->isPdf() && (
                $attachment->processing_status === InboxAttachmentProcessingStatus::Processing->value
                || $canRun
            );
        }

        if (! $canRun) {
            $this->setProgress(100);

            return;
        }

        $this->setProgress(10);

        $document->gateway_status = EBillingAttachmentProcessingStatus::Generating;
        $document->save();

        $billData = $document->bill_data;
        if (! is_array($billData) || $billData === []) {
            Log::error('GenerateArtifactJob: bill_data missing, cannot generate artifact without parsed data', [
                'ebilling_document_id' => $document->getKey(),
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(25);

        $dto = InvoiceDto::fromArray($billData);
        $invoice = $parsedInvoiceMapper->createFromDto($dto, $document);

        $this->setProgress(40);

        $invoiceFieldValidator->fillFieldValidations($document);
        $document->refresh();

        $formatId = $formatResolver->resolveForGeneration($document);
        $definition = $formatRegistry->get($formatId);
        $xml = $definition->strategy->generateXml(new ZugferdInvoiceAdapter($invoice), $definition->profile);

        $diskName = (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $scope = is_string($document->scope) && $document->scope !== '' ? $document->scope : 'manual';
        $relativeDir = $scope.'/'.EBillingArtifactNaming::invoiceDatePathSegment($invoice->invoice_date);

        $existingXmlPath = $document->xml_storage_path;
        if (
            is_string($existingXmlPath)
            && $existingXmlPath !== ''
            && Storage::disk($diskName)->exists($existingXmlPath)
        ) {
            $relativeXmlPath = $existingXmlPath;
        } else {
            $basename = EBillingArtifactNaming::uniqueBasenameFor($document->sourceOriginalFilename(), $diskName, $relativeDir);
            $relativeXmlPath = $relativeDir.'/'.$basename.'.xml';
        }

        Storage::disk($diskName)->put($relativeXmlPath, $xml);

        $this->setProgress(60);

        $relativePdfPath = null;
        $relativeCopyPdfPath = null;
        $preparedPdfPath = null;
        $normalizedPdfPath = null;

        try {
            if ($definition->artifactKind === ArtifactKind::Pdf) {
                $strategy = $definition->strategy;
                if (! $strategy instanceof HybridArtifactGeneratorStrategyInterface) {
                    throw new LogicException(
                        "Format [{$formatId}] declares a PDF artifact but its strategy does not "
                        .'implement HybridArtifactGeneratorStrategyInterface.'
                    );
                }

                $preparedPdfPath = $sourcePdfPreparer->prepare($document);
                $normalizedPdfPath = $pdfaNormalizer->normalize($preparedPdfPath);
                $pdfBinary = $strategy->mergeXmlIntoPdf($xml, $normalizedPdfPath);
                $basename = pathinfo($relativeXmlPath, PATHINFO_FILENAME);
                $dir = pathinfo($relativeXmlPath, PATHINFO_DIRNAME);
                $existingPdfPath = $document->pdf_storage_path;
                if (is_string($existingPdfPath) && $existingPdfPath !== '') {
                    $relativePdfPath = $existingPdfPath;
                } else {
                    $relativePdfPath = $dir.'/'.$basename.'.pdf';
                }

                Storage::disk($diskName)->put($relativePdfPath, $pdfBinary);
            } elseif ($definition->artifactKind === ArtifactKind::Xml) {
                $preparedPdfPath = $sourcePdfPreparer->prepare($document);
                $copyBinary = $copyPdfComposer->stamp($preparedPdfPath);
                $basename = pathinfo($relativeXmlPath, PATHINFO_FILENAME);
                $dir = pathinfo($relativeXmlPath, PATHINFO_DIRNAME);
                $existingCopyPath = $document->copy_pdf_storage_path;
                if (is_string($existingCopyPath) && $existingCopyPath !== '') {
                    $relativeCopyPdfPath = $existingCopyPath;
                } else {
                    $relativeCopyPdfPath = $dir.'/'.$basename.'_copy.pdf';
                }

                Storage::disk($diskName)->put($relativeCopyPdfPath, $copyBinary);
            }
        } finally {
            $this->cleanupTemporaryPdf($preparedPdfPath, $document);
            $this->cleanupTemporaryPdf($normalizedPdfPath, $document);
        }

        $this->setProgress(80);

        $billDataArray = $dto->toArray();

        $document->format = $formatId;
        $document->storage_disk = $diskName;
        $document->xml_storage_path = $relativeXmlPath;
        $document->pdf_storage_path = $relativePdfPath;
        $document->copy_pdf_storage_path = $relativeCopyPdfPath;
        $document->bill_data = $billDataArray;
        $document->artifact_content_hash = null;
        $document->gateway_status = EBillingAttachmentProcessingStatus::Validating;
        $document->save();

        $document->refresh();
        $invoiceFieldValidator->validate($document);

        $this->setProgress(90);

        event(new ArtifactGenerated($document->getKey(), $formatId));

        ValidateArtifactJob::dispatch($document->getKey());

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument) {
            return;
        }

        $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
        $document->save();

        $attachment = $document->inboxAttachment();
        $attachment?->markAsFailed($exception?->getMessage() ?? 'GenerateArtifactJob failed');

        if ($attachment !== null) {
            try {
                app(InboxMessagePipelineFinalizer::class)
                    ->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
            } catch (Throwable $e) {
                Log::error('[EBilling] GenerateArtifactJob failed() finalizer error', [
                    'exception' => $e,
                    'ebilling_document_id' => $document->getKey(),
                ]);
            }
        }
    }

    private function cleanupTemporaryPdf(?string $path, EbillingDocument $document): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        try {
            if ($path === $document->sourceFullPath()) {
                return;
            }
        } catch (Throwable) {
            // Source may already be gone; still try to remove the temp file.
        }

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
