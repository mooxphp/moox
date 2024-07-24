<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Moox\Core\Base\BaseListRecords;
use Moox\Expiry\Models\Expiry;
use Moox\Expiry\Resources\ExpiryResource;

class ListExpiries extends BaseListRecords
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
            dispatch(new $jobClass());
        }

        Notification::make()
            ->title('Aktualisieren gestartet')
            ->success()
            ->send();
    }

    public function getPresetViews(): array
    {
        if ($this->useAdvancedTables === true) {

            return [
                'Dokumente' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Dokumente'))
                    ->icon('heroicon-o-document-text')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Dokumente')->count())
                    ->favorite(),
                'Artikel' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Artikel'))
                    ->icon('heroicon-o-document-check')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Artikel')->count())
                    ->favorite(),
                'Aufgaben' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('expiry_job', 'Wiki Aufgaben'))
                    ->icon('heroicon-o-clipboard-document-list')
                    ->badge(Expiry::query()->where('expiry_job', 'Wiki Aufgaben')->count())
                    ->favorite(),
                'Kein Bearbeiter' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('status', 'Niemand verantwortlich'))
                    ->icon('heroicon-o-user-circle')
                    ->badge(Expiry::query()->where('status', 'Niemand verantwortlich')->count())
                    ->favorite(),
                'Ohne Datum' => \Archilex\AdvancedTables\Components\PresetView::make()
                    ->modifyQueryUsing(fn ($query) => $query->where('status', 'Kein Ablaufdatum'))
                    ->icon('heroicon-o-calendar-days')
                    ->badge(Expiry::query()->where('status', 'Kein Ablaufdatum')->count())
                    ->favorite(),
            ];
        }
    }
}
