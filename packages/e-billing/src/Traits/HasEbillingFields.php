<?php

declare(strict_types=1);

namespace Moox\EBilling\Traits;

use Moox\EBilling\Support\EbillingModelCasts;

trait HasEbillingFields
{
    public function initializeHasEbillingFields(): void
    {
        EbillingModelCasts::mergeInto($this);
    }
}
