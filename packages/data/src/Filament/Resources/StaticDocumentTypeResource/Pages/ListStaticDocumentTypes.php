<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticDocumentTypeResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource;
use Moox\Data\Models\StaticDocumentType;

class ListStaticDocumentTypes extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = StaticDocumentTypeResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('static-document-type.tabs', StaticDocumentType::class);
    }
}
