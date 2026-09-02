<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\EBilling\Enums\ApprovalTransitionKind;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Models\EbillingDocument;

final class DocumentApprovalTransitioned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EbillingDocument $document,
        public readonly ?DocumentApprovalStatus $from,
        public readonly DocumentApprovalStatus $to,
        public readonly ApprovalTransitionKind $kind,
        public readonly string $trigger,
    ) {
    }
}
