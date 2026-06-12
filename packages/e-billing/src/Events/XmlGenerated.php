<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

final class XmlGenerated
{
    public function __construct(
        public int $inboxAttachmentId,
    ) {}
}
