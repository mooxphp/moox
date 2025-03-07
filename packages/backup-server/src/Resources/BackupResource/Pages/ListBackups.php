<?php

namespace Moox\BackupServerUi\Resources\BackupResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Moox\BackupServerUi\Resources\BackupResource;
use Spatie\BackupServer\Models\Backup;
use Spatie\BackupServer\Models\BackupLogItem;
use Spatie\BackupServer\Models\Source;
use Spatie\BackupServer\Support\Helpers\Enums\LogLevel;
use Spatie\BackupServer\Tasks\Backup\Actions\CreateBackupAction;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label(__('Manual Backup'))
                ->modalHeading(__('Create Manual Backup'))
                ->form([
                    Select::make('source_id')
                        ->rules(['exists:backup_server_sources,id'])
                        ->required()
                        ->relationship('source', 'name')
                        ->placeholder('Source'),
                ])
                ->action(function (array $data): void {

                    $sourceName = Source::where('id', $data['source_id'])->pluck('name')->first();

                    $this->manualBackup($sourceName);

                }),
        ];
    }

    protected function manualBackup($sourceName): int
    {
        $source = Source::firstWhere('name', $sourceName);

        if (! $source) {
            Log::info('Manual Backup failed. There is no source named'.$sourceName);

            return -1;
        }

        Log::info('Manual Backup on sourceName: '.$sourceName.' created.');

        /* Logging needs to be fixed.
        $writeLogItemsToConsole = function (Backup $backup) {
            Event::listen('eloquent.saving: '.BackupLogItem::class, function (BackupLogItem $backupLogItem) use ($backup) {
                if ($backupLogItem->backup_id !== $backup->id) {
                    return;
                }

                $outputMethod = $backupLogItem->level === LogLevel::ERROR
                    ? 'error'
                    : 'comment';

                $this->$outputMethod($backupLogItem->message);
            });
        };
        */

        (new CreateBackupAction)
            ->doNotUseQueue()
            // ->afterBackupModelCreated($writeLogItemsToConsole)
            ->execute($source);

        return 0;
    }
}
