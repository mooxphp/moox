<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

final class XmlValidationFailed
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $inboxAttachmentId,
        public array $errors,
    ) {}
}
