<?php

namespace Moox\News\Resources\News\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\News\Models\News;
use Moox\News\Resources\NewsResource;

class ListNews extends BaseListDrafts
{
    use HasListPageTabs;

    protected static string $resource = NewsResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('news.resources.news.tabs', News::class);
    }
}
