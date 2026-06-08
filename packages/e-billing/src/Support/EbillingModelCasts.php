<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Attribute casts for gateway columns on Moox\MailInbox\Models\InboxAttachment.
 *
 * Registered from EBillingServiceProvider so moox/mail-inbox stays unchanged.
 */
final class EbillingModelCasts
{
    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return [
            'bill_data' => 'array',
            'ignored_reason' => 'array',
            'kosit_validation_id' => 'integer',
        ];
    }

    public static function mergeInto(Model $model): void
    {
        $model->mergeCasts(self::definitions());
    }
}
