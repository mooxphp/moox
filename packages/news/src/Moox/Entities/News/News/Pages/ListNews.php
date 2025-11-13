<?php

namespace Moox\News\Moox\Entities\News\News\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\News\Models\News;

class ListNews extends BaseListDrafts
{
    use HasListPageTabs;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('news.resources.news.tabs', News::class);
    }
}
