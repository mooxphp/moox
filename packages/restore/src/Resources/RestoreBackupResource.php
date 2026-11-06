<?php

declare(strict_types=1);

namespace Moox\Restore\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Moox\Restore\Models\RestoreBackup;
use Moox\Restore\Resources\RestoreBackupResource\Pages;
use Override;

class RestoreBackupResource extends Resource
{
    protected static ?string $model = RestoreBackup::class;

    protected static ?string $currentTab = null;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-restore-page';

    protected static ?string $authorModel = null;

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        static::initAuthorModel();

        return $schema->components([]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        static::initAuthorModel();

        $currentTab = static::getCurrentTab();

        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(__('restore::translations.status'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50)
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state)
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'in progress' => 'warning',
                        'created' => 'warning',
                        default => 'secondary',
                    }),
                TextColumn::make('backup.source.name')
                    ->label(__('restore::translations.source'))
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('backup.completed_at')
                    ->label(__('restore::translations.backup'))
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->dateTime(),
                TextColumn::make('restoreDestination.host')
                    ->label(__('restore::translations.destination'))
                    ->toggleable()
                    ->url(function (RestoreBackup $record): ?string {
                        $destination = $record->restoreDestination;

                        if ($destination === null) {
                            return null;
                        }

                        return 'https://'.$destination->host;
                    })
                    ->openUrlInNewTab()
                    ->icon('gmdi-link')
                    ->iconPosition(IconPosition::After)
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label(__('restore::translations.created-at'))
                    ->dateTime(),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label(__('jobs::translations.retry'))
                    ->requiresConfirmation()
                    ->action(function (RestoreBackup $record): void {
                        Artisan::call('mooxrestore:restore', [
                            'restoreBackup' => $record->id,
                        ]);
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->hidden(function () use ($currentTab): bool {
                    return in_array($currentTab, ['trash', 'deleted'], true);
                }),
                RestoreBulkAction::make()->visible(function () use ($currentTab): bool {
                    return in_array($currentTab, ['trash', 'deleted'], true);
                }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestoreBackups::route('/'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        $label = config('restore.resources.backup.single');

        return is_string($label) ? $label : 'Restore Backup';
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        $label = config('restore.resources.backup.plural');

        return is_string($label) ? $label : 'Restore Backups';
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return static::getModelLabel();
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        $group = config('restore.navigation_group');

        return is_string($group) ? $group : null;
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        $sort = config('restore.navigation_sort');

        return (is_int($sort) ? $sort : 0) + 3;
    }

    protected static function initAuthorModel(): void
    {
        if (static::$authorModel === null) {
            $authorModel = config('restore.author_model');
            static::$authorModel = is_string($authorModel) ? $authorModel : null;
        }
    }

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            $tab = request()->query('tab', '');
            static::$currentTab = is_string($tab) ? $tab : '';
        }

        return static::$currentTab !== '' ? static::$currentTab : null;
    }

    /**
     * @return Builder<RestoreBackup>
     */
    public static function getTableQuery(?string $currentTab = null): Builder
    {
        /** @var Builder<RestoreBackup> $query */
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
