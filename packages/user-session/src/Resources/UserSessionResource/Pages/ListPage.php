<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource;
use Moox\UserSession\Resources\UserSessionResource\Widgets\UserSessionWidgets;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = UserSessionResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // TODO: Widgets
            //UserSessionWidgets::class,
        ];
    }

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
