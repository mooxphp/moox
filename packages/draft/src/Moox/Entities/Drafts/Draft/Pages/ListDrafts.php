<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Draft\Models\Draft;
use Moox\Draft\Moox\Entities\Drafts\Draft\DraftResource;

class ListDrafts extends BaseListDrafts
{
    use HasListPageTabs;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('draft.resources.draft.tabs', Draft::class);
    }

    protected function getHeaderActions(): array
    {
        if (DraftResource::enableCreate()) {
            return [CreateAction::make()];
        }

        return [];
    }
}
