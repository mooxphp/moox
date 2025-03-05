<?php

namespace Moox\Jobs\Resources\JobBatchesResource\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Moox\Jobs\Resources\JobBatchesResource;

class ListJobBatches extends ListRecords
{
    protected static string $resource = JobBatchesResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('prune_batches')
                ->label(__('jobs::translations.prune_batches'))
                ->requiresConfirmation()
                ->color('danger')
                ->action(function (): void {
                    Artisan::call('queue:prune-batches');
                    Notification::make()
                        ->title(__('jobs::translations.prune_batches_notification'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
