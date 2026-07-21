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
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceOriginRule;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Jobs\Traits\JobProgress;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Services\GraphMailService;
use Throwable;

/**
 * After PDF parsing, classifies domestic vs. foreign invoice; foreign invoices are moved to a dedicated mailbox folder
 * and marked {@see EBillingAttachmentProcessingStatus::IgnoredForeign} without persisting an e-billing {@see Invoice} record.
 */
final class FilterForeignInvoiceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

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
        GraphMailService $graph,
    ): void {
        $this->setProgress(0);

        $attachment = InboxAttachment::query()->find($this->inboxAttachmentId);

        if ($attachment === null) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: attachment not found', [
                'inbox_attachment_id' => $this->inboxAttachmentId,
            ]);
            $this->setProgress(100);

            return;
        }

        if (! $attachment->isPdf()) {
            $this->setProgress(100);

            return;
        }

        $document = EbillingDocument::forSourceAttachment($attachment);

        if ($document?->gateway_status === EBillingAttachmentProcessingStatus::IgnoredForeign) {
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
            $this->dispatchGenerateArtifact($attachment);
            $this->setProgress(100);

            return;
        }

        if ($this->isEmptyParsedInvoiceArray($billData)) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: empty parsed invoice; continuing pipeline', [
                'inbox_attachment_id' => $attachment->id,
            ]);
            $this->dispatchGenerateArtifact($attachment);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(40);

        $classification = $this->classifyInvoiceOrigin($billData);
        if (! $classification['is_foreign']) {
            $this->dispatchGenerateArtifact($attachment);
            $this->setProgress(100);

            return;
        }

        $this->setProgress(60);

        $country = $classification['country'];
        $matchedRule = $classification['matched_rule']->value;

        $externalId = $message->external_id;
        if ($externalId === null || $externalId === '') {
            throw new \RuntimeException('FilterForeignInvoiceJob: inbox message has no external_id; cannot move in Graph.');
        }

        $folderName = (string) config('e-billing.foreign_invoice.ignored_folder_name', 'Ignored');

        $graph->moveMessageToFolderByName($externalId, $folderName, true);

        DB::transaction(function () use ($attachment, $document, $country, $matchedRule): void {
            $ignoredReason = [
                'country' => $country,
                'matched_rule' => $matchedRule,
                'classified_at' => now()->utc()->toIso8601String(),
            ];

            if ($document !== null) {
                $document->ignored_reason = $ignoredReason;
                $document->gateway_status = EBillingAttachmentProcessingStatus::IgnoredForeign;
                $document->save();
            }

            $attachment->error_message = null;
            $attachment->markAsSkipped();
        });

        Log::info("Foreign invoice ignored: attachment=#{$attachment->id} country=".($country ?? 'null').' matched_rule='.$matchedRule);

        $this->maybeMarkInboxMessageProcessedIfAllPdfAttachmentsTerminal($message->fresh());

        $this->setProgress(100);
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

    /**
     * @param  array<string, mixed>  $billData
     * @return array{is_foreign: bool, country: ?string, matched_rule: InvoiceOriginRule}
     */
    private function classifyInvoiceOrigin(array $billData): array
    {
        $country = $this->resolveBillingCountry($billData);
        $grossTotal = $this->toNullableFloat($billData['gross_total'] ?? null);
        $taxAmount = $this->toNullableFloat($billData['vat_amount'] ?? null);

        if ($country !== null && $country !== 'DE') {
            return ['is_foreign' => true, 'country' => $country, 'matched_rule' => InvoiceOriginRule::CountryDetected];
        }

        if ($country === 'DE') {
            return ['is_foreign' => false, 'country' => $country, 'matched_rule' => InvoiceOriginRule::CountryDetectedDe];
        }

        if ($this->isNetOnly($grossTotal, $taxAmount)) {
            return ['is_foreign' => true, 'country' => null, 'matched_rule' => InvoiceOriginRule::NetOnly];
        }

        return ['is_foreign' => false, 'country' => null, 'matched_rule' => InvoiceOriginRule::DefaultDe];
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
        if (! is_array($customerAddress)) {
            return $this->countryFromVatId($billData['customer_vat_id'] ?? null);
        }

        $country = $this->normalizeCountry($customerAddress['country'] ?? null);
        if ($country !== null) {
            return $country;
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

        $prefix = substr($normalized, 0, 2);

        return self::VAT_COUNTRY_PREFIXES[$prefix] ?? null;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function dispatchGenerateArtifact(InboxAttachment $attachment): void
    {
        $document = EbillingDocument::forSourceAttachment($attachment);
        $billData = $document?->bill_data;
        if (is_array($billData) && $billData !== [] && ! $this->hasExtractedInvoiceNumber($billData)) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: invoice_number (BT-1) missing; skipping XML generation', [
                'inbox_attachment_id' => $attachment->id,
                'invoice_number' => $billData['invoice_number'] ?? null,
            ]);

            return;
        }

        if ($document === null) {
            Log::warning('[EBilling] FilterForeignInvoiceJob: no ebilling document for generating promotion', [
                'inbox_attachment_id' => $attachment->id,
            ]);

            return;
        }

        $document->gateway_status = EBillingAttachmentProcessingStatus::Generating;
        $document->save();

        GenerateArtifactJob::dispatch($attachment->id);
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
            'inbox_attachment_id' => $this->inboxAttachmentId,
            'exception' => $exception,
        ]);
    }
}
