<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource;

class ListExpiries extends ListRecords
{
    use HasDynamicTabs;

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
        return $this->getDynamicTabs('expiry.expiry.tabs', Expiry::class);
    }
}
