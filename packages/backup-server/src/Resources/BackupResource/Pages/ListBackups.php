<?php

namespace Moox\BackupServerUi\Resources\BackupResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Moox\BackupServerUi\Resources\BackupResource;
use Moox\BackupServerUi\Resources\BackupResource\Widgets\BackupSizeStats;
use Spatie\BackupServer\Models\Backup;
use Spatie\BackupServer\Models\BackupLogItem;
use Spatie\BackupServer\Models\Source;
use Spatie\BackupServer\Support\Helpers\Enums\HasLogLevel;
use Spatie\BackupServer\Tasks\Backup\Actions\CreateBackupAction;

class ListBackups extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BackupResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            BackupSizeStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('Manual Backup')
                ->modalHeading('Create Manual Backup')
                ->schema([
                    Select::make('source_id')
                        ->rules(['exists:backup_server_sources,id'])
                        ->required()
                        ->relationship('source', 'name')
                        ->placeholder('Source'),
                ])
                ->action(function (array $data): void {
                    $sourceName = Source::query()->where('id', $data['source_id'])->value('name');

                    $this->manualBackup(is_string($sourceName) ? $sourceName : null);
                }),
        ];
    }

    protected function manualBackup(?string $sourceName): int
    {
        if ($sourceName === null) {
            Log::info('Manual Backup failed. Source name is missing.');

            return -1;
        }

        $source = Source::firstWhere('name', $sourceName);

        if (! $source) {
            Log::info('Manual Backup failed. There is no source named '.$sourceName);

            return -1;
        }

        Log::info('Manual Backup on sourceName: '.$sourceName.' created.');

        /* TODO: Logging needs to be fixed.
        $writeLogItemsToConsole = function (Backup $backup) {
            Event::listen('eloquent.saving: '.BackupLogItem::class, function (BackupLogItem $backupLogItem) use ($backup) {
                if ($backupLogItem->backup_id !== $backup->id) {
                    return;
                }

                $outputMethod = $backupLogItem->level === HasLogLevel::ERROR
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
