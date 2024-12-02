<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInListPage;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource;

class ListWpUsers extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.user.tabs', WpUser::class);
    }
}
