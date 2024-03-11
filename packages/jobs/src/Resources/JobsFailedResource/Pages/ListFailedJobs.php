<?php

namespace Moox\Jobs\Resources\JobsFailedResource\Pages;

use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Moox\Jobs\Models\FailedJob;
use Moox\Jobs\Resources\JobsFailedResource;

class ListFailedJobs extends ListRecords
{
    protected static string $resource = JobsFailedResource::class;

    public function getActions(): array
    {
        return [
            Action::make('retry_all')
                ->label(__('jobs::translations.retry_all_failed_jobs'))
                ->requiresConfirmation()
                ->action(function (): void {
                    Artisan::call('queue:retry all');
                    Notification::make()
                        ->title(__('jobs::translations.retry_all_failed_jobs_notification'))
                        ->success()
                        ->send();
                }),

            Action::make('delete_all')
                ->label(__('jobs::translations.delete_all_failed_jobs'))
                ->requiresConfirmation()
                ->color('danger')
                ->action(function (): void {
                    FailedJob::truncate();
                    Notification::make()
                        ->title(__('jobs::translations.delete_all_failed_jobs_notification'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
