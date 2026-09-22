<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Contracts\ForeignSourcePdfRelaySenderInterface;
use Moox\EBilling\Data\DeliveryOutcome;
use Moox\EBilling\Data\DeliveryRecipient;
use Moox\EBilling\Data\ForeignSourcePdfRelayRequest;
use Moox\EBilling\Models\EbillingDeliveryAttempt;
use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\MailInbox\Models\InboxMessage;

/**
 * Source-PDF relay for foreign invoices (ADR 0006).
 * Not e-invoice delivery — no approval, no Artifact.
 */
final class RelayForeignSourcePdfAction
{
    public const CHANNEL = 'source_pdf_relay';

    public const FAILURE_NO_RECIPIENT = 'no_recipient';

    public const FAILURE_INVALID_RECIPIENT = 'invalid_recipient';

    public const FAILURE_SELF_RECIPIENT = 'self_recipient';

    public const FAILURE_MISSING_SOURCE_PDF = 'missing_source_pdf';

    /** Outbox safe test mode redirected — transport ok, not delivered to intended. */
    public const FAILURE_SUPPRESSED = 'suppressed';

    /** @var list<string> */
    private const TERMINAL_FAILURES = [
        self::FAILURE_NO_RECIPIENT,
        self::FAILURE_INVALID_RECIPIENT,
        self::FAILURE_SELF_RECIPIENT,
        self::FAILURE_SUPPRESSED,
    ];

    public function __construct(
        private readonly ForeignSourcePdfRelaySenderInterface $sender,
        private readonly RecordDeliveryAttemptsAction $recordAttempts,
    ) {}

    public static function isTerminal(DeliveryOutcome $outcome): bool
    {
        if ($outcome->success) {
            return true;
        }

        return in_array($outcome->failureReason, self::TERMINAL_FAILURES, true);
    }

    public static function correlationId(string $externalId, string|int $attachmentId): string
    {
        return hash('sha256', 'ebilling:foreign-relay:'.$externalId.':'.$attachmentId);
    }

    public function execute(EbillingDocument $document): DeliveryOutcome
    {
        $attachment = $document->inboxAttachment();
        $message = $attachment?->message;

        if (! $attachment instanceof InboxAttachment || ! $message instanceof InboxMessage) {
            return $this->recordFailure($document, '', self::FAILURE_NO_RECIPIENT);
        }

        $externalId = (string) ($message->external_id ?? '');
        $correlationId = self::correlationId($externalId !== '' ? $externalId : 'missing', $attachment->getKey());

        $priorAttempt = EbillingDeliveryAttempt::query()
            ->where('ebilling_document_id', $document->getKey())
            ->where('channel', self::CHANNEL)
            ->where('correlation_id', $correlationId)
            ->latest('id')
            ->first();

        if ($priorAttempt instanceof EbillingDeliveryAttempt) {
            $priorOutcome = new DeliveryOutcome(
                recipient: (string) $priorAttempt->recipient,
                success: (bool) $priorAttempt->success,
                failureReason: $priorAttempt->failure_reason,
                correlationId: $correlationId,
            );

            if (self::isTerminal($priorOutcome)) {
                return $priorOutcome;
            }
        }

        $rawTo = trim((string) ($message->to_email ?? ''));
        if ($rawTo === '') {
            return $this->recordFailure($document, '', self::FAILURE_NO_RECIPIENT, $correlationId);
        }

        if (! filter_var($rawTo, FILTER_VALIDATE_EMAIL)) {
            return $this->recordFailure($document, $rawTo, self::FAILURE_INVALID_RECIPIENT, $correlationId);
        }

        $scope = (string) ($message->scope ?? 'default');
        $mailboxAddress = trim((string) config('mail-inbox.mailboxes.'.$scope.'.address', ''));
        if ($mailboxAddress !== '' && strcasecmp($rawTo, $mailboxAddress) === 0) {
            return $this->recordFailure($document, $rawTo, self::FAILURE_SELF_RECIPIENT, $correlationId);
        }

        $pdfDisk = $document->sourceStorageDisk();
        $pdfPath = $document->sourceStoragePath();
        if (
            ! is_string($pdfDisk) || $pdfDisk === ''
            || ! is_string($pdfPath) || $pdfPath === ''
            || ! Storage::disk($pdfDisk)->exists($pdfPath)
        ) {
            return $this->recordFailure($document, $rawTo, self::FAILURE_MISSING_SOURCE_PDF, $correlationId);
        }

        $recipient = new DeliveryRecipient(
            address: $rawTo,
            name: $document->inboxToName(),
            kind: 'inbox_to',
        );

        $request = new ForeignSourcePdfRelayRequest(
            document: $document,
            recipient: $recipient,
            subject: $message->subject,
            bodyText: $message->raw_body_text,
            bodyHtml: $message->raw_body_html,
            pdfDisk: $pdfDisk,
            pdfPath: $pdfPath,
            pdfFilename: $document->sourceOriginalFilename(),
            correlationId: $correlationId,
        );

        $result = $this->sender->send($request);

        $outcome = new DeliveryOutcome(
            recipient: $result->actualRecipient ?? $recipient->address,
            success: $result->accepted,
            failureReason: $result->accepted ? null : ($result->failureReason ?? 'send_not_accepted'),
            correlationId: $result->correlationId ?? $correlationId,
        );

        $this->recordAttempts->execute($document, self::CHANNEL, [$outcome]);

        return $outcome;
    }

    private function recordFailure(
        EbillingDocument $document,
        string $recipient,
        string $failureReason,
        ?string $correlationId = null,
    ): DeliveryOutcome {
        $outcome = new DeliveryOutcome(
            recipient: $recipient,
            success: false,
            failureReason: $failureReason,
            correlationId: $correlationId,
        );
        $this->recordAttempts->execute($document, self::CHANNEL, [$outcome]);

        return $outcome;
    }
}
