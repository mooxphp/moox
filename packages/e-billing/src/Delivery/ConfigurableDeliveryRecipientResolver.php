<?php

declare(strict_types=1);

namespace Moox\EBilling\Delivery;

use Moox\EBilling\Contracts\DeliveryRecipientResolverInterface;
use Moox\EBilling\Data\DeliveryRecipient;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\MailInbox\Models\InboxAttachment;

/**
 * Config-driven recipient policy for {@see MailDeliveryChannel}.
 *
 * Strategies (`e-billing.delivery.recipients.*`):
 * - `inbox_to` — InboxMessage.to_email for mail-sourced documents
 * - `master` — attributed company.email
 * - `none` — no recipients (channel records no_recipient)
 */
final class ConfigurableDeliveryRecipientResolver implements DeliveryRecipientResolverInterface
{
    public const STRATEGY_INBOX_TO = 'inbox_to';

    public const STRATEGY_MASTER = 'master';

    public const STRATEGY_NONE = 'none';

    /**
     * @return list<DeliveryRecipient>
     */
    public function resolve(EbillingDocument $document): array
    {
        $strategy = $this->strategyFor($document);

        return match ($strategy) {
            self::STRATEGY_INBOX_TO => $this->fromInboxTo($document),
            self::STRATEGY_MASTER => $this->fromMaster($document),
            default => [],
        };
    }

    private function strategyFor(EbillingDocument $document): string
    {
        $config = config('e-billing.delivery.recipients', []);
        $config = is_array($config) ? $config : [];

        if ($document->inboxAttachment() !== null) {
            $strategy = $config['mail_source'] ?? self::STRATEGY_INBOX_TO;
        } elseif ($document->source instanceof UploadedPdfSource) {
            $strategy = $config['manual_upload'] ?? self::STRATEGY_NONE;
        } else {
            $strategy = self::STRATEGY_NONE;
        }

        return is_string($strategy) && $strategy !== '' ? $strategy : self::STRATEGY_NONE;
    }

    /**
     * @return list<DeliveryRecipient>
     */
    private function fromInboxTo(EbillingDocument $document): array
    {
        $attachment = $document->inboxAttachment();

        if (! $attachment instanceof InboxAttachment) {
            return [];
        }

        $attachment->loadMissing('message');
        $message = $attachment->message;

        if ($message === null) {
            return [];
        }

        $email = trim((string) ($message->to_email ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        $name = trim((string) ($message->to_name ?? ''));

        return [
            new DeliveryRecipient(
                address: $email,
                name: $name !== '' ? $name : null,
                kind: 'to',
            ),
        ];
    }

    /**
     * @return list<DeliveryRecipient>
     */
    private function fromMaster(EbillingDocument $document): array
    {
        $document->loadMissing('company');
        $company = $document->company;

        if ($company === null) {
            return [];
        }

        $email = trim((string) ($company->email ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        $name = trim((string) ($company->name ?? ''));

        return [
            new DeliveryRecipient(
                address: $email,
                name: $name !== '' ? $name : null,
                kind: 'to',
            ),
        ];
    }
}
