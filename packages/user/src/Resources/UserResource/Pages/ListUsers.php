<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInPage;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource;

class ListUsers extends ListRecords
{
    use TabsInPage;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('user.resources.user.tabs', User::class);
    }
}
