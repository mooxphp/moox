<?php

namespace Moox\UserSession\Resources\UserSessionResource\Pages;

use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
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
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(UserSession::query()->count())
                ->icon('gmdi-filter-list'),
            'user' => Tab::make('User Sessions')
                ->badge(UserSession::query()->where('user_id', '!=', null)->count())
                ->icon('gmdi-account-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', '!=', null)),
            'anonymous' => Tab::make('Anonymous Sessions')
                ->badge(UserSession::query()->where('user_id', null)->count())
                ->icon('gmdi-no-accounts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', null)),
        ];
    }
}
