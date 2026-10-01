<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Support\ParsedValueHistory;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\En16931\Party;

/**
 * Applies the recipient address declared at manual upload once, when the invoice is first mapped.
 */
final class ApplyUploadedRecipientEmailAction
{
    public const ACTIVITY_EVENT = SetRecipientEmailAction::ACTIVITY_EVENT;

    public const ACTIVITY_EVENT_FAILED = 'recipient_set_failed';

    public const ORIGIN_UPLOAD = 'upload';

    /**
     * @return bool true when the address was written to the invoice
     */
    public function execute(EbillingDocument $document): bool
    {
        $source = $document->source;

        if (! $source instanceof UploadedPdfSource) {
            return false;
        }

        if ($source->recipient_email_applied_at !== null) {
            return false;
        }

        $email = trim((string) ($source->recipient_email ?? ''));

        if ($email === '') {
            return false;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordFailure($document, $source, $email, 'invalid_email');

            return false;
        }

        $invoice = $document->invoice;

        if (! $invoice instanceof Invoice) {
            $this->recordFailure($document, $source, $email, 'no_invoice');

            return false;
        }

        $buyer = $invoice->buyer;

        if (! $buyer instanceof Party) {
            $this->recordFailure($document, $source, $email, 'no_buyer_party');

            return false;
        }

        $data = $buyer->toArray();
        $previous = $data['contact']['email'] ?? null;

        if ($previous !== $email) {
            $data['contact'] = [...($data['contact'] ?? ['name' => '', 'phone' => null]), 'email' => $email];
            $invoice->buyer = Party::fromArray($data);
            $invoice->save();
            $this->recordSuccess($invoice, $source, $previous, $email);
        }

        $source->forceFill(['recipient_email_applied_at' => now()])->save();

        return true;
    }

    private function recordSuccess(Invoice $invoice, UploadedPdfSource $source, mixed $previous, string $email): void
    {
        if (! ParsedValueHistory::isAvailable()) {
            Log::warning('[EBilling] Upload recipient applied without audit trail', [
                'uploaded_pdf_source_id' => $source->getKey(),
                'invoice_id' => $invoice->getKey(),
            ]);

            return;
        }

        $causer = $this->resolveUploaderCauser($source);

        $activity = MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
            'event' => self::ACTIVITY_EVENT,
            'entry_type' => 'audit',
            'subject' => $invoice,
            'causer' => $causer,
            'properties' => [
                'field' => SetRecipientEmailAction::ATTRIBUTE,
                'previous_value' => $previous,
                'value' => $email,
                'origin' => self::ORIGIN_UPLOAD,
            ],
        ]);

        if ($activity === null) {
            Log::warning('[EBilling] Upload recipient applied but activity was not recorded', [
                'uploaded_pdf_source_id' => $source->getKey(),
            ]);
        }
    }

    private function recordFailure(
        EbillingDocument $document,
        UploadedPdfSource $source,
        string $email,
        string $reason,
    ): void {
        Log::warning('[EBilling] Upload recipient could not be applied', [
            'ebilling_document_id' => $document->getKey(),
            'uploaded_pdf_source_id' => $source->getKey(),
            'reason' => $reason,
        ]);

        if (! ParsedValueHistory::isAvailable()) {
            return;
        }

        $invoice = $document->invoice;

        MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT_FAILED, [
            'event' => self::ACTIVITY_EVENT_FAILED,
            'entry_type' => 'audit',
            'subject' => $invoice instanceof Invoice ? $invoice : $document,
            'causer' => $this->resolveUploaderCauser($source),
            'properties' => [
                'field' => SetRecipientEmailAction::ATTRIBUTE,
                'value' => $email,
                'origin' => self::ORIGIN_UPLOAD,
                'reason' => $reason,
            ],
        ]);
    }

    private function resolveUploaderCauser(UploadedPdfSource $source): ?Model
    {
        $userId = $source->uploader_user_id;

        if (! is_string($userId) || $userId === '') {
            return null;
        }

        $modelClass = config('auth.providers.users.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass::query()->find($userId);
    }
}
