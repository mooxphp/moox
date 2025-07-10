<?php

namespace Moox\News\Moox\Entities\News\News\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\News\Moox\Entities\News\News\NewsResource;

class ListNews extends BaseListDrafts
{
    use HasListPageTabs;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.simple-news.tabs', NewsResource::class);
    }

    protected function getHeaderActions(): array
    {
        if (NewsResource::enableCreate()) {
            return [CreateAction::make()];
        }

        return [];
    }
}
