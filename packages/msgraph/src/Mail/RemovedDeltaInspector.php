<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Microsoft\Graph\Generated\Models\Message;

/**
 * Detects Graph delta tombstone rows so they are not mapped to inbox messages.
 */
final class RemovedDeltaInspector
{
    public static function isRemoved(Message $message): bool
    {
        $extra = $message->getAdditionalData();

        if ($extra === null) {
            return false;
        }

        return array_key_exists('@removed', $extra);
    }
}
