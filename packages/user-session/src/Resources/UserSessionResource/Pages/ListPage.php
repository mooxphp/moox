<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource;
use Moox\UserSession\Resources\UserSessionResource\Widgets\UserSessionWidgets;

class ListPage extends ListRecords
{
    public static string $resource = UserSessionResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            UserSessionWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('user-session::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): UserSession {
                    return $model::create($data);
                }),
        ];
    }
}
