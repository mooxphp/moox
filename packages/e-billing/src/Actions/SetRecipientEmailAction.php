<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Moox\Audit\Services\MooxActivityLogger;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\ParsedValueHistory;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\En16931\Party;
use RuntimeException;

/**
 * A reviewer sets the address the document is delivered to, stored as `buyer.contact.email` (BT-58).
 * For a mail-sourced document it is a value correction: the parser could have read it. A manually uploaded
 * document has no mail to read it from, so setting it is its own audited act, `recipient_set`, and stays out
 * of the parser-feedback data (ADR 0004 addendum).
 */
final class SetRecipientEmailAction
{
    public const ACTIVITY_EVENT = 'recipient_set';

    public const ATTRIBUTE = 'buyer.contact.email';

    public function __construct(
        private readonly CorrectFieldValueAction $correctFieldValue,
    ) {
    }

    /**
     * @return bool false when the address is unchanged
     */
    public function execute(EbillingDocument $document, ?string $email, ?string $note = null): bool
    {
        $email = trim((string) $email);
        $email = $email === '' ? null : $email;

        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("{$email} is not a valid e-mail address.");
        }

        if ($document->inboxAttachment() !== null) {
            $invoice = $document->invoice;
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException("Document #{$document->id} has no linked invoice.");
            }

            return $this->correctFieldValue->execute($document, $invoice, self::ATTRIBUTE, $email, $note);
        }

        return $this->recordRecipient($document, $email, $note);
    }

    private function recordRecipient(EbillingDocument $document, ?string $email, ?string $note): bool
    {
        if (auth()->user() === null) {
            throw new InvalidArgumentException('An authenticated actor is required to set the recipient.');
        }

        if (! ParsedValueHistory::isAvailable()) {
            throw new RuntimeException('Setting the recipient requires moox/audit with auditing enabled.');
        }

        return DB::transaction(function () use ($document, $email, $note): bool {
            $lockedDocument = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            if ($lockedDocument->resolveApprovalStatusEnum() !== DocumentApprovalStatus::Pending) {
                throw new InvalidArgumentException('Only a pending document can be changed.');
            }

            $invoice = $lockedDocument->invoice()->lockForUpdate()->first();
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException("Document #{$document->id} has no linked invoice.");
            }

            $buyer = $invoice->buyer;
            if (! $buyer instanceof Party) {
                throw new InvalidArgumentException('The document has no buyer to set a recipient on.');
            }

            $data = $buyer->toArray();
            $previous = $data['contact']['email'] ?? null;
            if ($previous === $email) {
                return false;
            }

            $data['contact'] = [...($data['contact'] ?? ['name' => '', 'phone' => null]), 'email' => $email];
            $invoice->buyer = Party::fromArray($data);

            if (! $invoice->save()) {
                throw new RuntimeException('The recipient could not be saved.');
            }

            $activity = MooxActivityLogger::log('e-billing', self::ACTIVITY_EVENT, [
                'event' => self::ACTIVITY_EVENT,
                'entry_type' => 'audit',
                'subject' => $invoice,
                'properties' => [
                    'field' => self::ATTRIBUTE,
                    'previous_value' => $previous,
                    'value' => $email,
                    'note' => $note,
                ],
            ]);

            if ($activity === null) {
                throw new RuntimeException('Setting the recipient could not be recorded.');
            }

            return true;
        });
    }
}
