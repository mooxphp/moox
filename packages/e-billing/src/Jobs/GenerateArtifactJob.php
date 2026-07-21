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
use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Events\ArtifactGenerated;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\Contracts\HybridArtifactGeneratorStrategyInterface;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Services\ParsedInvoiceMapper;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\EBilling\Support\EBillingFormatResolver;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;
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
        public int $inboxAttachmentId,
    ) {}

    public function handle(
        FormatRegistry $formatRegistry,
        EBillingFormatResolver $formatResolver,
        ParsedInvoiceMapper $parsedInvoiceMapper,
        InvoiceFieldValidator $invoiceFieldValidator,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] GenerateArtifactJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        $retryableStatuses = [
            EBillingAttachmentProcessingStatus::Generating,
            EBillingAttachmentProcessingStatus::GenerationFailed,
            EBillingAttachmentProcessingStatus::Validating,
            EBillingAttachmentProcessingStatus::ValidationFailed,
            EBillingAttachmentProcessingStatus::ValidatorError,
        ];

        $canRun = $attachment->isPdf() && (
            $attachment->processing_status === InboxAttachmentProcessingStatus::Processing->value
            || in_array($document?->gateway_status, $retryableStatuses, true)
        );

        if (! $canRun) {
            $this->setProgress(100);

            return;
        }

        $this->setProgress(10);

        if ($document !== null) {
            $document->gateway_status = EBillingAttachmentProcessingStatus::Generating;
            $document->save();
        }

        $billData = $document?->bill_data;
        if (! is_array($billData) || $billData === []) {
            Log::error('GenerateArtifactJob: bill_data missing, cannot generate artifact without parsed data', [
                'attachment_id' => $attachment->id,
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(25);

        $dto = InvoiceDto::fromArray($billData);
        $invoice = $parsedInvoiceMapper->createFromDto($dto, $attachment);

        $this->setProgress(40);

        $formatId = $document !== null
            ? $formatResolver->resolveForGeneration($document)
            : (string) config('e-billing.default_format', 'zugferd');
        $definition = $formatRegistry->get($formatId);
        $xml = $definition->strategy->generateXml(new ZugferdInvoiceAdapter($invoice), $definition->profile);

        $diskName = (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $relativeDir = $attachment->scope.'/'.EBillingArtifactNaming::invoiceDatePathSegment($invoice->invoice_date);

        $existingXmlPath = $document?->xml_storage_path;
        if (
            is_string($existingXmlPath)
            && $existingXmlPath !== ''
            && Storage::disk($diskName)->exists($existingXmlPath)
        ) {
            $relativeXmlPath = $existingXmlPath;
        } else {
            $basename = EBillingArtifactNaming::uniqueBasenameFor($attachment, $diskName, $relativeDir);
            $relativeXmlPath = $relativeDir.'/'.$basename.'.xml';
        }

        Storage::disk($diskName)->put($relativeXmlPath, $xml);

        $this->setProgress(60);

        $relativePdfPath = null;

        if ($definition->artifactKind === ArtifactKind::Pdf) {
            $strategy = $definition->strategy;
            if (! $strategy instanceof HybridArtifactGeneratorStrategyInterface) {
                throw new LogicException("Format [{$formatId}] declares a PDF artifact but its strategy does not implement HybridArtifactGeneratorStrategyInterface.");
            }

            $pdfBinary = $strategy->mergeXmlIntoPdf($xml, $attachment->fullPath());
            $basename = pathinfo($relativeXmlPath, PATHINFO_FILENAME);
            $dir = pathinfo($relativeXmlPath, PATHINFO_DIRNAME);
            $existingPdfPath = $document?->pdf_storage_path;
            if (is_string($existingPdfPath) && $existingPdfPath !== '') {
                $relativePdfPath = $existingPdfPath;
            } else {
                $relativePdfPath = $dir.'/'.$basename.'.pdf';
            }

            Storage::disk($diskName)->put($relativePdfPath, $pdfBinary);
        }

        $this->setProgress(80);

        $billDataArray = $dto->toArray();

        if ($document !== null) {
            $document->format = $formatId;
            $document->storage_disk = $diskName;
            $document->xml_storage_path = $relativeXmlPath;
            $document->pdf_storage_path = $relativePdfPath;
            $document->bill_data = $billDataArray;
            $document->artifact_content_hash = null;
            $document->gateway_status = EBillingAttachmentProcessingStatus::Validating;
            $document->save();
        }

        if ($document !== null) {
            $document->refresh();
            $invoiceFieldValidator->validate($document);
        }

        $this->setProgress(90);

        event(new ArtifactGenerated($attachment->id, $formatId));

        ValidateArtifactJob::dispatch($attachment->id);

        $this->setProgress(100);
    }

    public function failed(?Throwable $exception = null): void
    {
        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document !== null) {
            $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
            $document->save();
        }

        $attachment->markAsFailed($exception?->getMessage() ?? 'GenerateArtifactJob failed');

        try {
            app(InboxMessagePipelineFinalizer::class)->finalizeAfterAttachmentPipelineStep($attachment->inbox_message_id);
        } catch (Throwable $e) {
            Log::error('[EBilling] GenerateArtifactJob failed() finalizer error', [
                'exception' => $e,
                'inbox_attachment_id' => $attachment->id,
            ]);
        }
    }
}
