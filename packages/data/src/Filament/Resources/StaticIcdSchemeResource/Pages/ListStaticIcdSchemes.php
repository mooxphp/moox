<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticIcdSchemeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticIcdSchemeResource;
use Moox\Data\Models\StaticIcdScheme;

class ListStaticIcdSchemes extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticIcdSchemeResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-icd-scheme.tabs', StaticIcdScheme::class);
    }
}
