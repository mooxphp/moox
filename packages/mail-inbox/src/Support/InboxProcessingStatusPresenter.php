<?php

declare(strict_types=1);

namespace Moox\MailInbox\Support;

use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;

final class InboxProcessingStatusPresenter
{
    public static function messageLabel(?string $status): string
    {
        $enum = InboxMessageProcessingStatus::tryFrom((string) $status);

        if ($enum === null) {
            return $status !== null && $status !== '' ? $status : '—';
        }

        return match ($enum) {
            InboxMessageProcessingStatus::New => __('mail-inbox::fields.status_new'),
            InboxMessageProcessingStatus::Read => __('mail-inbox::fields.status_read'),
            InboxMessageProcessingStatus::Processed => __('mail-inbox::fields.status_processed'),
            InboxMessageProcessingStatus::Failed => __('mail-inbox::fields.status_failed'),
            InboxMessageProcessingStatus::PartiallyFailed => __('mail-inbox::fields.status_partially_failed'),
            InboxMessageProcessingStatus::Skipped => __('mail-inbox::fields.status_skipped'),
        };
    }

    public static function messageColor(?string $status): string
    {
        $enum = InboxMessageProcessingStatus::tryFrom((string) $status);

        return match ($enum) {
            InboxMessageProcessingStatus::New => 'info',
            InboxMessageProcessingStatus::Read => 'gray',
            InboxMessageProcessingStatus::Processed => 'success',
            InboxMessageProcessingStatus::Failed => 'danger',
            InboxMessageProcessingStatus::PartiallyFailed => 'warning',
            InboxMessageProcessingStatus::Skipped => 'gray',
            default => 'gray',
        };
    }

    public static function attachmentLabel(?string $status): string
    {
        $enum = InboxAttachmentProcessingStatus::tryFrom((string) $status);

        if ($enum === null) {
            return $status !== null && $status !== '' ? $status : '—';
        }

        return match ($enum) {
            InboxAttachmentProcessingStatus::New => __('mail-inbox::fields.status_new'),
            InboxAttachmentProcessingStatus::Processing => __('mail-inbox::fields.status_processing'),
            InboxAttachmentProcessingStatus::Processed => __('mail-inbox::fields.status_processed'),
            InboxAttachmentProcessingStatus::Failed => __('mail-inbox::fields.status_failed'),
            InboxAttachmentProcessingStatus::Skipped => __('mail-inbox::fields.status_skipped'),
        };
    }

    public static function attachmentColor(?string $status): string
    {
        $enum = InboxAttachmentProcessingStatus::tryFrom((string) $status);

        return match ($enum) {
            InboxAttachmentProcessingStatus::New => 'info',
            InboxAttachmentProcessingStatus::Processing => 'warning',
            InboxAttachmentProcessingStatus::Processed => 'success',
            InboxAttachmentProcessingStatus::Failed => 'danger',
            InboxAttachmentProcessingStatus::Skipped => 'gray',
            default => 'gray',
        };
    }

    public static function truncateDiagnosticBlob(?string $value, int $limit = 80): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit).'…';
    }

    public static function mailboxAddressForScope(string $scope): ?string
    {
        $mailboxes = config('mail-inbox.mailboxes', []);
        if (! is_array($mailboxes)) {
            return null;
        }

        $mailbox = $mailboxes[$scope] ?? null;
        if (! is_array($mailbox)) {
            return null;
        }

        $address = $mailbox['address'] ?? null;

        return is_string($address) && $address !== '' ? $address : null;
    }
}
