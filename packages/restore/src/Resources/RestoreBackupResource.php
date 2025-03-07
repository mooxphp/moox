<?php

declare(strict_types=1);

namespace Moox\Restore\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Moox\Restore\Models\RestoreBackup;
use Moox\Restore\Resources\RestoreBackupResource\Pages;

// use Moox\Core\Forms\Components\TitleWithSlugInput;

class RestoreBackupResource extends Resource
{
    protected static ?string $model = RestoreBackup::class;

    protected static ?string $currentTab = null;

    protected static ?string $navigationIcon = 'gmdi-restore-page';

    protected static ?string $authorModel = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Form $form): Form
    {
        static::initAuthorModel();

        return $form->schema([]);
    }

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
                    ->url(function ($record) {
                        $host = $record->restoreDestination->host;

                        return "https://$host";
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
            ->actions([
                Action::make('retry')
                    ->label(__('jobs::translations.retry'))
                    ->requiresConfirmation()
                    ->action(function (RestoreBackup $record): void {
                        Artisan::call('mooxrestore:restore', [
                            'restoreBackup' => $record->id,
                        ]);
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(function () use ($currentTab) {
                    $isHidden = in_array($currentTab, ['trash', 'deleted']);

                    return $isHidden;
                }),
                RestoreBulkAction::make()->visible(function () use ($currentTab) {
                    $isVisible = in_array($currentTab, ['trash', 'deleted']);

                    return $isVisible;
                }),
            ])
            ->filters([
            /*SelectFilter::make('type')
                    ->options(static::getModel()::getTypeOptions())
                    ->label(__('core::core.type')),*/])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestoreBackups::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            // RestoreBackupWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('restore.resources.backup.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('restore.resources.backup.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('restore.resources.backup.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('restore.resources.backup.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('restore.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('restore.navigation_sort') + 3;
    }

    protected static function initAuthorModel(): void
    {
        if (static::$authorModel === null) {
            static::$authorModel = config('restore.author_model');
        }
    }

    protected static function getAuthorOptions(): array
    {
        return static::$authorModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowAuthorField(): bool
    {
        return static::$authorModel && class_exists(static::$authorModel);
    }

    public static function getCurrentTab(): ?string
    {
        if (static::$currentTab === null) {
            static::$currentTab = request()->query('tab', '');
        }

        return static::$currentTab ?: null;
    }

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            $model = static::getModel();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->whereNotNull($model::make()->getQualifiedDeletedAtColumn());
            }
        }

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
