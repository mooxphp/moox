<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticPaymentMeanResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticPaymentMeanResource;
use Moox\DataLegacy\Models\StaticPaymentMean;

class ListStaticPaymentMeans extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticPaymentMeanResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-payment-mean.tabs', StaticPaymentMean::class);
    }
}
