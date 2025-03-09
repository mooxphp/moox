<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource;
use Moox\UserSession\Resources\UserSessionResource\Widgets\UserSessionWidgets;
use Override;

class ListPage extends ListRecords
{
    use HasListPageTabs;

    public static string $resource = UserSessionResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            // TODO: Widgets
            // UserSessionWidgets::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('core::session.title');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('user-session.resources.session.tabs', UserSession::class);
    }
}
