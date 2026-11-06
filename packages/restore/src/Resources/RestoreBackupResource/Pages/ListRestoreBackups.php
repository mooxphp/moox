<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreBackupResource\Pages;

use Carbon\Carbon;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Moox\Restore\Models\RestoreBackup;
use Moox\Restore\Models\RestoreDestination;
use Moox\Restore\Resources\RestoreBackupResource;
use Override;
use Spatie\BackupServer\Models\Backup;

class ListRestoreBackups extends ListRecords
{
    protected static string $resource = RestoreBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restore_backup')
                ->label(__('Manual Restore Backup'))
                ->modalHeading(__('Manual Restore'))
                ->schema([
                    Select::make('restoreDestinationId')
                        ->label(__('Select Restore Destination'))
                        ->options(RestoreDestination::query()->pluck('host', 'id'))
                        ->required()
                        ->placeholder(__('Select a destination'))
                        ->afterStateUpdated(fn (Set $set) => $set('backupId', null))
                        ->live(),

                    Select::make('backupId')
                        ->label(__('Select Backup to restore'))
                        ->options(function (Get $get): array {
                            $destinationId = $get('restoreDestinationId');

                            if (! is_numeric($destinationId) && ! is_string($destinationId)) {
                                return [];
                            }

                            $destination = RestoreDestination::query()->find($destinationId);

                            if (! $destination instanceof RestoreDestination || $destination->source === null) {
                                return [];
                            }

                            $backups = Backup::query()
                                ->where('source_id', $destination->source->getKey())
                                ->whereNotNull('completed_at')
                                ->orderByDesc('completed_at')
                                ->pluck('completed_at', 'id');

                            return $backups
                                ->mapWithKeys(function (mixed $date, int|string $id): array {
                                    $parsed = match (true) {
                                        $date instanceof DateTimeInterface, is_string($date), is_int($date), is_float($date) => Carbon::parse($date),
                                        default => null,
                                    };

                                    return [$id => $parsed?->format('d.m.Y H:i:s') ?? ''];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->placeholder(__('Select Backup'))
                        ->hidden(fn (Get $get): bool => blank($get('restoreDestinationId'))),
                ])
                ->action(function (array $data): void {
                    $restoreDestination = RestoreDestination::query()
                        ->whereKey($data['restoreDestinationId'])
                        ->firstOrFail();
                    $backup = Backup::query()
                        ->whereKey($data['backupId'])
                        ->firstOrFail();

                    $restoreBackup = RestoreBackup::query()->create([
                        'backup_id' => $backup->getKey(),
                        'restore_destination_id' => $restoreDestination->getKey(),
                        'status' => 'created',
                    ]);

                    Artisan::call('moox-restore:restore', [
                        'restoreBackup' => $restoreBackup->getKey(),
                    ]);
                }),
            Action::make('dispatch_restore')
                ->label(__('Dispatch Restore'))
                ->icon('gmdi-refresh')
                ->action(function (): void {
                    Artisan::call('moox-restore:dispatch-restore');
                }),
        ];
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        $title = config('restore.resources.backup.plural');

        return is_string($title) ? $title : 'Restore Backups';
    }
}
