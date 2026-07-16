<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource;
use Moox\Data\Models\StaticVatExemptionReason;

class ListStaticVatExemptionReasons extends BaseListStatic
{
    use HasListPageTabs;

    protected static string $resource = StaticVatExemptionReasonResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-vat-exemption-reason.tabs', StaticVatExemptionReason::class);
    }
}
