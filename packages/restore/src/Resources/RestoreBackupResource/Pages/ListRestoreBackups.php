<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreBackupResource\Pages;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Moox\Restore\Models\RestoreBackup;
use Spatie\BackupServer\Models\Backup;
use Illuminate\Support\Facades\Artisan;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Resources\RestoreBackupResource;

class ListRestoreBackups extends ListRecords
{

    public static string $resource = RestoreBackupResource::class;

    public function mount(): void
    {
        parent::mount();
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore_backup')
                ->label(__('Manual Restore Backup'))
                ->modalHeading(__('Manual Restore'))
                ->form([
                    Select::make('restoreDestinationId')
                        ->label(__('Select Restore Destination'))
                        ->options(RestoreDestination::all()->pluck('host', 'id'))
                        ->required()
                        ->placeholder(__('Select a destination'))
                        ->afterStateUpdated(fn(Set $set) => $set('backupId', null))
                        ->reactive(),

                    Select::make('backupId')
                        ->label(__('Select Backup to restore'))
                        ->options(function (Get $get) {
                            if ($get('restoreDestinationId')) {
                                $sourceId = RestoreDestination::find($get('restoreDestinationId'))->source->id;
                                $backups = Backup::where('source_id', $sourceId)->whereNotNull('completed_at')->orderBy('completed_at', 'desc')->pluck('completed_at', 'id');
                                $formattedBackups = $backups->mapWithKeys(function ($date, $id) {
                                    return [$id => \Carbon\Carbon::parse($date)->format('d.m.Y H:i:s')];
                                });
                                return $formattedBackups;
                            }
                            return [];
                        })
                        ->searchable()
                        ->required()
                        ->placeholder(__('Select Backup'))
                        ->hidden(function (Get $get) {
                            return !$get('restoreDestinationId');
                        }),

                ])
                ->action(function (array $data): void {
                    $restoreDestination = RestoreDestination::findOrFail($data['restoreDestinationId']);
                    $backup = Backup::findOrFail($data['backupId']);
                    if ($restoreDestination && $backup) {
                        $restoreBackup = RestoreBackup::create([
                            'backup_id' => $backup->id,
                            'restore_destination_id' => $restoreDestination->id,
                            'status' => 'created'
                        ]);
                    }
                    Artisan::call('moox-restore:restore', [
                        'restoreBackup' => $restoreBackup->id
                    ]);
                }),
            Action::make('dispatch_restore')
                ->label(__('Dispatch Restore'))
                ->icon('gmdi-refresh')
                ->action(function (): void {
                    Artisan::call('moox-restore:dispatch-restore');
                })

        ];
    }

    public function getTitle(): string
    {
        return config('restore.resources.backup.plural');
    }
}
