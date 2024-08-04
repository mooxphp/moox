<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Config;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource;

class ListExpiries extends ListRecords
{
    protected static string $resource = ExpiryResource::class;

    protected function getHeaderActions(): array
    {
        return config('expiry.collect_expiries_action')
            ? [
                Action::make('collectExpiries')
                    ->label('Expiries aktualisieren')
                    ->requiresConfirmation()
                    ->action(function () {
                        self::collectExpiries();
                    }),
            ]
            : [];
    }

    public static function collectExpiries()
    {
        $jobs = config('expiry.collect_expiries_jobs', []);
        foreach ($jobs as $jobClass) {
            dispatch(new $jobClass);
        }

        Notification::make()
            ->title('Aktualisieren gestartet')
            ->success()
            ->send();
    }

    public function getTabs(): array
    {
        $tabsConfig = Config::get('expiry.expiry.tabs', []);
        $tabs = [];

        foreach ($tabsConfig as $key => $tabConfig) {
            if ($key === 'all') {
                $tabs[$key] = Tab::make($tabConfig['label'])
                    ->modifyQueryUsing(fn ($query) => $query)
                    ->badge(Expiry::query()->count())
                    ->icon($tabConfig['icon']);
            } else {
                $tabs[$key] = Tab::make($tabConfig['label'])
                    ->modifyQueryUsing(fn ($query) => $query->where($tabConfig['field'], $tabConfig['value']))
                    ->badge(Expiry::query()->where($tabConfig['field'], $tabConfig['value'])->count())
                    ->icon($tabConfig['icon']);
            }
        }

        return $tabs;
    }
}
