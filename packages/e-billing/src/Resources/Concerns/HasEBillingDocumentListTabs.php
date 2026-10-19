<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\Concerns;

use Moox\Core\Traits\Tabs\HasListPageTabs;

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
        return $this->getDynamicTabs(
            static::getResource()::tabsConfigPath(),
            static::getResource()::getModel(),
        );
    }

    protected function applyConditions($query, $conditions)
    {
        return static::getResource()::applyListTabConditions($query, $conditions);
    }
}
