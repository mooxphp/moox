<?php

declare(strict_types=1);

namespace Moox\MailInbox\Support;

use Microsoft\Graph\Generated\Models\Message;

/**
 * Shared delta-row helpers so ingestion paths stay aligned with Graph @removed semantics.
 */
final class DeltaMessageInspector
{
    /**
     * True when Graph delta returned a tombstone row ({@code @removed}) instead of a full message payload.
     */
    public static function isRemovedPlaceholder(Message $message): bool
    {
        $extra = $message->getAdditionalData();

        if ($extra === null) {
            return false;
        }

        return array_key_exists('@removed', $extra);
    }
}
