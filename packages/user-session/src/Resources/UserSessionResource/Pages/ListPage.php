<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource;
use Override;

class ListPage extends BaseListItems
{
    use HasListPageTabs;

    public static string $resource = UserSessionResource::class;

    #[Override]
    public function getTitle(): string
    {
        return __('core::session.title');
    }

    public function getHeaderActions(): array
    {
        return [];
    }

    #[Override]
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $records = parent::getTableRecords();

        $items = $records instanceof Paginator || $records instanceof CursorPaginator
            ? collect($records->items())
            : collect($records);

        UserSession::hydrateRelations($items);

        return $records;
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('user-session.resources.session.tabs', UserSession::class);
    }
}
