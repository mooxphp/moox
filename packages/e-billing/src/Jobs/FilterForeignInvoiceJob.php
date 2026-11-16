<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moox\EBilling\Actions\MarkDocumentForeignAction;
use Moox\EBilling\Actions\RelayForeignSourcePdfAction;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\ForeignDisposition;
use Moox\EBilling\Enums\InvoiceOriginRule;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\ForeignInvoiceClassifier;
use Moox\EBilling\Support\ForeignUploadNotifier;
use Moox\EBilling\Support\IntakeCutoff;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxDriverManager;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use RuntimeException;
use Throwable;

/**
 * After PDF parsing, classifies domestic vs. foreign invoice; foreign invoices are settled as
 * Ignored on the inbox driver and marked {@see EBillingAttachmentProcessingStatus::IgnoredForeign}
 * without persisting an e-billing {@see Invoice} record. Domestic mail-sourced invoices dated before
 * the {@see IntakeCutoff} take the same path as {@see EBillingAttachmentProcessingStatus::IgnoredBeforeCutoff}.
 * Uploads are classified only when `e-billing.foreign.sources` lists `upload`.
 */
final class FilterForeignInvoiceJob implements ShouldQueue
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
        InboxDriverManager $drivers,
    ): void {
        $this->setProgress(0);

        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: document not found', [
                'ebilling_document_id' => $this->ebillingDocumentId,
            ]);
            $this->setProgress(100);

            return;
        }

        if (in_array($document->gateway_status, [
            EBillingAttachmentProcessingStatus::IgnoredForeign,
            EBillingAttachmentProcessingStatus::IgnoredBeforeCutoff,
        ], true)) {
            $this->setProgress(100);

            return;
        }

        $attachment = $document->inboxAttachment();
        $classifier = app(ForeignInvoiceClassifier::class);

        if ($attachment === null) {
            if ($this->ignoreForeignUpload($classifier, $document)) {
                $this->setProgress(100);

                return;
            }

            $this->dispatchGenerateArtifact($document);
            $this->setProgress(100);

            return;
        }

        if (! $attachment->isPdf()) {
            $this->setProgress(100);

            return;
        }

        if ($attachment->processing_status !== InboxAttachmentProcessingStatus::Processing->value) {
            $this->setProgress(100);

            return;
        }

        $message = $attachment->message;
        if ($message === null) {
            Log::error('[EBilling] FilterForeignInvoiceJob: attachment has no message', [
                'inbox_attachment_id' => $attachment->id,
            ]);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(10);

        $billData = $document?->bill_data;

        if (empty($billData) || ! is_array($billData)) {
            Log::warning('FilterForeignInvoiceJob: bill_data missing on ebilling document', [
                'attachment_id' => $attachment->id,
            ]);
            $this->dispatchGenerateArtifact($document);
            $this->setProgress(100);

            return;
        }

        if ($this->isEmptyParsedInvoiceArray($billData)) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: empty parsed invoice; continuing pipeline', [
                'inbox_attachment_id' => $attachment->id,
            ]);
            $this->dispatchGenerateArtifact($document);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(40);

        $cutoff = IntakeCutoff::fromConfig();

        $classification = $classifier->appliesTo(ForeignInvoiceClassifier::SOURCE_MAIL)
            ? $classifier->classify($billData)
            : null;
        $markedTwin = $classification !== null ? $this->markedForeignTwin($document, $classification) : null;
        if ($classification !== null && ($classification['is_foreign'] || $markedTwin !== null)) {
            $this->setProgress(60);

            $country = $classification['country'];
            $matchedRule = $classification['matched_rule']->value;
            $disposition = ForeignDisposition::fromConfig();

            $this->settleIgnored($drivers, $document, $attachment, $message, $disposition, EBillingAttachmentProcessingStatus::IgnoredForeign, $this->foreignReason($classification, $markedTwin));

            Log::info(
                'Foreign invoice '.$disposition->value.': attachment=#'.$attachment->id.' country='
                    .($country ?? 'null')
                    .' matched_rule='.$matchedRule
            );

            $this->setProgress(100);

            return;
        }

        $cutoffReason = $cutoff?->ignoredReason($billData);
        if ($cutoff !== null && $cutoffReason !== null) {
            $this->setProgress(60);

            $disposition = $cutoff->disposition();

            $this->settleIgnored($drivers, $document, $attachment, $message, $disposition, EBillingAttachmentProcessingStatus::IgnoredBeforeCutoff, $cutoffReason);

            Log::info(
                'Invoice before intake cutoff '.$disposition->value.': attachment=#'.$attachment->id
                    .' invoice_date='.$cutoffReason['invoice_date']
                    .' invoice_date_from='.$cutoffReason['invoice_date_from']
            );

            $this->setProgress(100);

            return;
        }

        if ($classification !== null) {
            $this->flagIfUnclear($document, $classification, $billData);
        }

        $this->dispatchGenerateArtifact($document);
        $this->setProgress(100);
    }

    /**
     * Relay the source PDF when the disposition asks for it, settle the inbox message as Ignored,
     * and end the document in the given ignored gateway status without an Invoice or Artifact.
     *
     * @param  array<string, mixed>  $reason
     */
    private function settleIgnored(
        InboxDriverManager $drivers,
        EbillingDocument $document,
        InboxAttachment $attachment,
        InboxMessage $message,
        ForeignDisposition $disposition,
        EBillingAttachmentProcessingStatus $status,
        array $reason,
    ): void {
        $externalId = $message->external_id;
        if ($externalId === null || $externalId === '') {
            throw new RuntimeException(
                'FilterForeignInvoiceJob: inbox message has no external_id; cannot settle as Ignored.',
            );
        }

        if ($disposition === ForeignDisposition::Forward) {
            $outcome = app(RelayForeignSourcePdfAction::class)->execute($document);
            if (! RelayForeignSourcePdfAction::isTerminal($outcome)) {
                throw new RuntimeException(
                    'FilterForeignInvoiceJob: source-PDF relay not accepted: '
                    .($outcome->failureReason ?? 'send_not_accepted'),
                );
            }
        }

        $drivers->mailbox((string) ($message->scope ?? 'default'))
            ->settle($externalId, SettlementOutcome::Ignored);

        DB::transaction(function () use ($attachment, $document, $status, $reason): void {
            $document->ignored_reason = [
                ...$reason,
                'classified_at' => now()->utc()->toIso8601String(),
            ];
            $document->gateway_status = $status;
            $document->save();

            $attachment->error_message = null;
            $attachment->markAsSkipped();
        });

        $this->maybeMarkInboxMessageProcessedIfAllPdfAttachmentsTerminal($message->fresh());
    }

    /**
     * A foreign upload has no inbox message to settle or relay: it ends ignored, and the uploader is told
     * to send the original PDF the usual way, whatever the disposition. An unclear upload is flagged and
     * held for a person, then generated like a domestic one.
     */
    private function ignoreForeignUpload(ForeignInvoiceClassifier $classifier, EbillingDocument $document): bool
    {
        $billData = $document->bill_data;

        if (! $classifier->appliesTo(ForeignInvoiceClassifier::SOURCE_UPLOAD)
            || ! $document->source instanceof UploadedPdfSource
            || ! is_array($billData)
            || $billData === []
            || $this->isEmptyParsedInvoiceArray($billData)) {
            return false;
        }

        $classification = $classifier->classify($billData);
        $markedTwin = $this->markedForeignTwin($document, $classification);
        if (! $classification['is_foreign'] && $markedTwin === null) {
            $this->flagIfUnclear($document, $classification, $billData);

            return false;
        }

        $document->ignored_reason = [
            ...$this->foreignReason($classification, $markedTwin),
            'classified_at' => now()->utc()->toIso8601String(),
        ];
        $document->gateway_status = EBillingAttachmentProcessingStatus::IgnoredForeign;
        $document->save();

        app(ForeignUploadNotifier::class)->notify($document);

        Log::info('Foreign upload ignored: document=#'.$document->getKey()
            .' country='.($classification['country'] ?? 'null')
            .' matched_rule='.$classification['matched_rule']->value);

        return true;
    }

    /**
     * @param  array{is_foreign: bool, country: ?string, matched_rule: InvoiceOriginRule}  $classification
     * @param  array<string, mixed>  $billData
     */
    private function flagIfUnclear(EbillingDocument $document, array $classification, array $billData): void
    {
        if (! $classification['matched_rule']->needsReview()) {
            return;
        }

        $document->flagForeignReview($classification, $billData);
        $document->save();
    }

    /**
     * The `ignored_reason` of a foreign document; one that took a reviewer's decision points to that document.
     *
     * @param  array{is_foreign: bool, country: ?string, matched_rule: InvoiceOriginRule}  $classification
     * @return array<string, string|null>
     */
    private function foreignReason(array $classification, ?EbillingDocument $markedTwin): array
    {
        $reason = ['country' => $classification['country'], 'matched_rule' => $classification['matched_rule']->value];
        if ($markedTwin !== null) {
            $reason['matched_document_id'] = (string) $markedTwin->getKey();
        }

        return $reason;
    }

    /**
     * For an unclear document: an earlier document with the same source PDF bytes that a reviewer marked foreign
     * ({@see MarkDocumentForeignAction}). The same PDF takes that decision instead of a second review.
     *
     * @param  array{is_foreign: bool, country: ?string, matched_rule: InvoiceOriginRule}  $classification
     */
    private function markedForeignTwin(EbillingDocument $document, array $classification): ?EbillingDocument
    {
        $hash = $document->source_content_hash;
        if (! $classification['matched_rule']->needsReview() || ! is_string($hash) || $hash === '') {
            return null;
        }

        return EbillingDocument::query()
            ->whereKeyNot($document->getKey())
            ->where('source_content_hash', $hash)
            ->where('gateway_status', EBillingAttachmentProcessingStatus::IgnoredForeign)
            ->whereNotNull('ignored_reason->marked_by')
            ->orderBy('created_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function isEmptyParsedInvoiceArray(array $parsed): bool
    {
        $addr = $parsed['customer_address'] ?? null;

        return ($parsed['invoice_number'] ?? '') === ''
            && ($parsed['customer_name'] ?? '') === ''
            && ($addr === null || $addr === [] || $addr === '');
    }

    private function dispatchGenerateArtifact(EbillingDocument $document): void
    {
        $billData = $document->bill_data;
        if (is_array($billData) && $billData !== [] && ! $this->hasExtractedInvoiceNumber($billData)) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: invoice_number (BT-1) missing; skipping XML generation', [
                'ebilling_document_id' => $document->getKey(),
                'invoice_number' => $billData['invoice_number'] ?? null,
            ]);

            return;
        }

        $document->gateway_status = EBillingAttachmentProcessingStatus::Generating;
        $document->save();

        GenerateArtifactJob::dispatch($document->getKey());
    }

    /**
     * @param  array<string, mixed>  $billData
     */
    private function hasExtractedInvoiceNumber(array $billData): bool
    {
        $invoiceNumber = $billData['invoice_number'] ?? '';

        return is_string($invoiceNumber) && trim($invoiceNumber) !== '';
    }

    private function maybeMarkInboxMessageProcessedIfAllPdfAttachmentsTerminal(?InboxMessage $message): void
    {
        if ($message === null) {
            return;
        }

        $message->load('attachments');
        $pdfs = $message->pdfAttachments()->get();

        if ($pdfs->isEmpty()) {
            return;
        }

        foreach ($pdfs as $pdf) {
            if ($this->attachmentPipelineStillInFlight($pdf)) {
                return;
            }
        }

        $hasFailure = $pdfs->contains(fn (InboxAttachment $a): bool => $this->attachmentIsPipelineFailed($a));

        if ($hasFailure) {
            $error = $message->error_message !== null && $message->error_message !== ''
                ? $message->error_message
                : 'One or more attachments failed processing';
            $message->markAsPartiallyFailed($error);

            return;
        }

        $message->error_message = null;
        $message->markAsProcessed();
    }

    private function attachmentPipelineStillInFlight(InboxAttachment $attachment): bool
    {
        if (in_array($attachment->processing_status, [
            InboxAttachmentProcessingStatus::New->value,
            InboxAttachmentProcessingStatus::Processing->value,
        ], true)) {
            return true;
        }

        $gatewayStatus = EbillingDocument::forSourceAttachment($attachment)?->gateway_status;

        return in_array($gatewayStatus, [
            EBillingAttachmentProcessingStatus::Generating,
            EBillingAttachmentProcessingStatus::Validating,
        ], true);
    }

    private function attachmentIsPipelineFailed(InboxAttachment $attachment): bool
    {
        if ($attachment->processing_status === InboxAttachmentProcessingStatus::Failed->value) {
            return true;
        }

        return in_array(EbillingDocument::forSourceAttachment($attachment)?->gateway_status, [
            EBillingAttachmentProcessingStatus::GenerationFailed,
            EBillingAttachmentProcessingStatus::ValidationFailed,
            EBillingAttachmentProcessingStatus::ValidatorError,
        ], true);
    }

    public function failed(?Throwable $exception = null): void
    {
        Log::error('[EBilling] FilterForeignInvoiceJob failed', [
            'ebilling_document_id' => $this->ebillingDocumentId,
            'exception' => $exception,
        ]);

        $document = EbillingDocument::query()->find($this->ebillingDocumentId);

        if (! $document instanceof EbillingDocument || $document->inboxAttachment() !== null) {
            return;
        }

        $document->gateway_status = EBillingAttachmentProcessingStatus::GenerationFailed;
        $document->save();
    }
}
