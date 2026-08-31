<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Moox\MailOutbox\Enums\MailSendSource;
use Moox\MailOutbox\Enums\MailSendStatus;

final class MailSendStatusPresenter
{
    public static function label(MailSendStatus|string|null $status): string
    {
        $value = $status instanceof MailSendStatus ? $status->value : (string) $status;

        return match ($value) {
            MailSendStatus::Queued->value => __('mail-outbox::fields.status_queued'),
            MailSendStatus::Sent->value => __('mail-outbox::fields.status_sent'),
            MailSendStatus::Failed->value => __('mail-outbox::fields.status_failed'),
            MailSendStatus::Suppressed->value => __('mail-outbox::fields.status_suppressed'),
            default => $value !== '' ? $value : '—',
        };
    }

    public static function color(MailSendStatus|string|null $status): string
    {
        $value = $status instanceof MailSendStatus ? $status->value : (string) $status;

        return match ($value) {
            MailSendStatus::Queued->value => 'warning',
            MailSendStatus::Sent->value => 'success',
            MailSendStatus::Failed->value => 'danger',
            MailSendStatus::Suppressed->value => 'gray',
            default => 'gray',
        };
    }

    public static function sourceLabel(MailSendSource|string|null $source): string
    {
        $value = $source instanceof MailSendSource ? $source->value : (string) $source;

        return match ($value) {
            MailSendSource::Outbox->value => __('mail-outbox::fields.source_outbox'),
            MailSendSource::Recorded->value => __('mail-outbox::fields.source_recorded'),
            default => $value !== '' ? $value : '—',
        };
    }
}
