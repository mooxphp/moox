<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

final class XmlValidationPassed
{
    public function __construct(
        public int $inboxAttachmentId,
    ) {}
}
