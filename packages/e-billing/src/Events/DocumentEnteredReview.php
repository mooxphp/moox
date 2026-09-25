<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\EBilling\Models\EbillingDocument;

/**
 * Fired when a document enters (or re-enters) dispatch-approval review.
 * Hosts may listen for notifications; the package does not send mail itself.
 */
final class DocumentEnteredReview
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public readonly EbillingDocument $document,
        public readonly array $reasons,
    ) {
    }
}
