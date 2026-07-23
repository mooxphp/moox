<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticPaymentMeanResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource;
use Moox\Data\Models\StaticPaymentMean;

class ListStaticPaymentMeans extends BaseListStatic
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
