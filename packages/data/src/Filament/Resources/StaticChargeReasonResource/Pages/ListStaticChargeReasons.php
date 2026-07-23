<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticChargeReasonResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticChargeReasonResource;
use Moox\Data\Models\StaticChargeReason;

class ListStaticChargeReasons extends BaseListStatic
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
