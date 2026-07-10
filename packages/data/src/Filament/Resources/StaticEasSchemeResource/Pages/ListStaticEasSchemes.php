<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticEasSchemeResource;
use Moox\Data\Models\StaticEasScheme;

class ListStaticEasSchemes extends BaseListStatic
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
