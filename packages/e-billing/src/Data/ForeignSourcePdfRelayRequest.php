<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Models\EbillingDocument;

final readonly class ForeignSourcePdfRelayRequest
{
    public function __construct(
        public EbillingDocument $document,
        public DeliveryRecipient $recipient,
        public ?string $subject,
        public ?string $bodyText,
        public ?string $bodyHtml,
        public string $pdfDisk,
        public string $pdfPath,
        public string $pdfFilename,
        public string $correlationId,
    ) {
    }
}
