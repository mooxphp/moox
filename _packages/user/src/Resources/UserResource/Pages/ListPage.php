<?php

namespace Moox\User\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\User\Resources\UserResource;

class ListPage extends ListRecords
{
    public static string $resource = UserResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('user::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): User {
                    return $model::create($data);
                }),
        ];
    }
}
