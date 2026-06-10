<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticVatExemptionReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticVatExemptionReasonResource;
use Moox\DataLegacy\Models\StaticVatExemptionReason;

class ListStaticVatExemptionReasons extends BaseListRecords
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
