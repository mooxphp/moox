<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Facades\Lang;

/**
 * Translates known delivery failure machine keys for UI/Activity.
 * Unknown / free-text reasons (provider errors) pass through unchanged.
 */
final class DeliveryFailureReasonLabels
{
    public static function label(?string $reason): ?string
    {
        if ($reason === null || $reason === '') {
            return null;
        }

        $key = 'e-billing::fields.delivery_failure_reasons.'.$reason;

        if (! Lang::has($key)) {
            return $reason;
        }

        return __($key);
    }
}
