<?php

namespace Moox\LoginLink\Resources\LoginLinkResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Resources\LoginLinkResource;
use Override;

class ListPage extends ListRecords
{
    use HasListPageTabs;

    public static string $resource = LoginLinkResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('login-link::translations.title');
    }

    protected function getHeaderActions(): array
    {
        // Login links should be created via the request flow (mail), not manually in the admin UI.
        return [];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('login-link.resources.login-link.tabs', LoginLink::class);
    }
}
