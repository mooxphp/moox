<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticChargeReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticChargeReasonResource;
use Moox\DataLegacy\Models\StaticChargeReason;

class ListStaticChargeReasons extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticChargeReasonResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-charge-reason.tabs', StaticChargeReason::class);
    }
}
