<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Config;
use Moox\Core\Traits\QueriesInConfig;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource;

class ListExpiries extends ListRecords
{
    use QueriesInConfig;

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
            $tab = Tab::make($tabConfig['label'])
                ->icon($tabConfig['icon']);

            $queryConditions = $tabConfig['query'];

            if (empty($queryConditions)) {
                $tab->modifyQueryUsing(fn ($query) => $query)
                    ->badge(Expiry::query()->count());
            } else {
                $tab->modifyQueryUsing(function ($query) use ($queryConditions) {
                    return $this->applyConditions($query, $queryConditions);
                });

                $badgeCountQuery = Expiry::query();
                $badgeCountQuery = $this->applyConditions($badgeCountQuery, $queryConditions);
                $tab->badge($badgeCountQuery->count());
            }

            $tabs[$key] = $tab;
        }

        return $tabs;
    }
}
