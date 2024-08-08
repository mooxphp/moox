<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\User\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;
use Moox\User\Models\User;

class ListUsers extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('user.user.tabs', User::class);
    }
}
