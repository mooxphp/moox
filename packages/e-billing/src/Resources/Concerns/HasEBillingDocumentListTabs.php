<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\Concerns;

use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Invoice\Support\InvoiceModels;

trait HasEBillingDocumentListTabs
{
    use HasListPageTabs;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('e-billing.tabs.invoices', InvoiceModels::invoice());
    }

    protected function applyConditions($query, $conditions)
    {
        return static::getResource()::applyListTabConditions($query, $conditions);
    }
}
