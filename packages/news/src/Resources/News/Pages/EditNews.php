<?php

namespace Moox\News\Resources\News\Pages;

use Filament\Resources\Concerns\HasTabs;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\News\Models\News;
use Moox\News\Resources\NewsResource;

class EditNews extends BaseEditDraft
{
    use HasListPageTabs {
        HasListPageTabs::updatedActiveTab insteadof HasTabs;
    }
    use HasTabs;

    protected static string $resource = NewsResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('news.resources.news.tabs', News::class);
    }
}
