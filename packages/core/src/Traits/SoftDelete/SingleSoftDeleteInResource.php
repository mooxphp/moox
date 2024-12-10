<?php

namespace Moox\Core\Traits\SoftDelete;

use Filament\Forms\Components\Actions;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait SingleSoftDeleteInResource
{
    public static function getSoftDeleteEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->color('primary')
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $record && ! $record->trashed());
    }

    public static function getSoftDeleteViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->color('secondary')
            ->url(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $record && ! $record->trashed());
    }

    public static function getTableActions(): array
    {
        return [
            static::getSoftDeleteEditTableAction(),
            static::getSoftDeleteViewTableAction(),
            static::getRestoreTableAction(),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            static::getRestoreBulkAction(),
            static::getDeleteBulkAction(),
        ];
    }

    public static function getFormActions(): Actions
    {
        return Actions::make([
            static::getSaveAction(),
            static::getSaveAndCreateAnotherAction(),
            static::getCancelAction(),
            static::getDeleteAction(),
            static::getEditAction(),
            static::getRestoreAction(),
        ]);
    }

    protected static function applySoftDeleteQuery(Builder $query): Builder
    {
        $model = static::getModel();

        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && request()->query('activeTab') === 'deleted') {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }
}
