<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Models\EbillingDocument;

interface ReviewNotificationStrategyInterface
{
    /**
     * @param  list<string>  $reasons
     */
    public function announce(EbillingDocument $document, array $reasons): void;
}
