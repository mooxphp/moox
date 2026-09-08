<?php

namespace Moox\LoginLink\Resources\LoginLinkResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Resources\LoginLinkResource;
use Override;

class ListPage extends BaseListItems
{
    use HasListPageTabs;

    public static string $resource = LoginLinkResource::class;

    #[Override]
    public function getTitle(): string
    {
        return __('login-link::translations.title');
    }

    public function getHeaderActions(): array
    {
        // Login links should be created via the request flow (mail), not manually in the admin UI.
        return [];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('login-link.resources.login-link.tabs', LoginLink::class);
    }
}
