<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticEasSchemeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\DataLegacy\Filament\Resources\StaticEasSchemeResource;
use Moox\DataLegacy\Models\StaticEasScheme;

class ListStaticEasSchemes extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticEasSchemeResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-eas-scheme.tabs', StaticEasScheme::class);
    }
}
