<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticAllowanceReasonResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticAllowanceReasonResource;
use Moox\Data\Models\StaticAllowanceReason;

class ListStaticAllowanceReasons extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticAllowanceReasonResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-allowance-reason.tabs', StaticAllowanceReason::class);
    }
}
