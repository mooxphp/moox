<?php

declare(strict_types=1);

namespace Moox\Migrate\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Migrate\Models\Migration;
use Moox\Migrate\Resources\MigrationResource\Pages\ListMigrations;
use Moox\Migrate\Support\MigrateRunStatus;
use Moox\Migrate\Support\MigrationInventory;
use Override;

class MigrationResource extends BaseItemResource
{
    protected static ?string $model = Migration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    public static function enableCreate(): bool
    {
        return false;
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function enableView(): bool
    {
        return false;
    }

    public static function enableDelete(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('core::core.id'))
                    ->sortable(),
                TextColumn::make('migration')
                    ->label(__('migrate::migrate.migration'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('batch')
                    ->label(__('migrate::migrate.batch'))
                    ->sortable(),
            ])
            ->defaultSort('batch', 'desc')
            ->filters([
                SelectFilter::make('batch')
                    ->label(__('migrate::migrate.batch'))
                    ->options(fn (): array => Migration::query()
                        ->distinct()
                        ->orderByDesc('batch')
                        ->pluck('batch', 'batch')
                        ->all()),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->poll(fn (): ?string => MigrateRunStatus::isActive() ? '2s' : null)
            ->deferFilters(false);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListMigrations::route('/'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('migrate.resources.migration.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('migrate.resources.migration.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('migrate.resources.migration.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('migrate.resources.migration.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('migrate.navigation_group');
    }

    #[Override]
    public static function getNavigationBadge(): ?string
    {
        $count = app(MigrationInventory::class)->pendingCount();

        return $count > 0 ? (string) $count : null;
    }

    #[Override]
    public static function getNavigationBadgeColor(): string|array|null
    {
        return app(MigrationInventory::class)->pendingCount() > 0 ? 'warning' : null;
    }

    #[Override]
    public static function getNavigationBadgeTooltip(): string|Htmlable|null
    {
        $count = app(MigrationInventory::class)->pendingCount();

        if ($count === 0) {
            return null;
        }

        return trans_choice('migrate::migrate.pending_heading', $count, ['count' => $count]);
    }
}
