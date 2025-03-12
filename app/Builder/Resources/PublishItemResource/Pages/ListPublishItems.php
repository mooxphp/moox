<?php

declare(strict_types=1);

namespace App\Builder\Resources\PublishItemResource\Pages;

use App\Builder\Models\PublishItem;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Publish\SinglePublishInListPage;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListPublishItems extends ListRecords
{
    use BaseInListPage, HasListPageTabs, SinglePublishInListPage;

    protected static string $resource = \App\Builder\Resources\PublishItemResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('previews.publish-item.tabs', \App\Builder\Models\PublishItem::class);
    }

    protected function applyStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'published' => PublishItem::published(),
            'scheduled' => PublishItem::scheduled(),
            'draft' => PublishItem::draft(),
            default => $query,
        };
    }

    protected function getTableQuery(): Builder
    {
        return PublishItem::query();
    }
}
