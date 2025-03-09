<?php

namespace Moox\LoginLink\Resources\LoginLinkResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Resources\LoginLinkResource;
use Moox\LoginLink\Resources\LoginLinkResource\Widgets\LoginLinkWidgets;
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
        return [
            LoginLinkWidgets::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('login-link::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): LoginLink => $model::create($data)),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('login-link.resources.login-link.tabs', LoginLink::class);
    }
}
