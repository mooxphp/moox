<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Data\ForeignSourcePdfRelayRequest;
use Moox\EBilling\Data\InvoiceMailSendResult;

/**
 * Host-bound transport for Source-PDF relay of foreign invoices (ADR 0006).
 * Implementations may use mail-outbox, Laravel Mail, or another stack.
 */
interface ForeignSourcePdfRelaySenderInterface
{
    public function send(ForeignSourcePdfRelayRequest $request): InvoiceMailSendResult;
}
