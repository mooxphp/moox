<?php

namespace Moox\Core\Traits\SoftDelete;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait SingleSoftDeleteInResource
{
    public static function enableCreate(): bool
    {
        return true;
    }

    public static function enableEdit(): bool
    {
        return true;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        return true;
    }

    public static function enableHardDelete(): bool
    {
        return true;
    }

    public static function enableRestore(): bool
    {
        return true;
    }

    public static function enableEmptyTrash(): bool
    {
        return true;
    }

    public static function getSoftDeleteEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->color('primary')
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $record instanceof \Illuminate\Database\Eloquent\Model && method_exists($record, 'trashed') && ! $record->trashed());
    }

    public static function getSoftDeleteViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->color('secondary')
            ->url(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $record instanceof \Illuminate\Database\Eloquent\Model && method_exists($record, 'trashed') && ! $record->trashed());
    }

    public static function getHardDeleteTableAction(): Action
    {
        return Action::make('hardDelete')
            ->color('danger')
            ->label(__('core::core.hard_delete'))
            ->icon('heroicon-m-trash')
            ->requiresConfirmation()
            ->modalHeading(__('core::core.hard_delete_confirmation'))
            ->modalDescription(__('core::core.hard_delete_description'))
            ->action(function ($record) {
                $record->forceDelete();
            })
            ->visible(fn ($livewire, $record) => $record instanceof \Illuminate\Database\Eloquent\Model && method_exists($record, 'trashed') && $record->trashed());
    }

    public static function getTableActions(): array
    {
        $actions = [];

        if (static::enableEdit()) {
            $actions[] = static::getSoftDeleteEditTableAction();
        }

        if (static::enableView()) {
            $actions[] = static::getSoftDeleteViewTableAction();
        }

        if (static::enableHardDelete()) {
            $actions[] = static::getHardDeleteTableAction();
        }

        if (static::enableRestore()) {
            $actions[] = static::getRestoreTableAction();
        }

        return $actions;
    }

    public static function getHardDeleteBulkAction(): BulkAction
    {
        return BulkAction::make('hardDelete')
            ->color('danger')
            ->label(__('core::core.hard_delete_selected'))
            ->icon('heroicon-m-trash')
            ->modalHeading(__('core::core.hard_delete_bulk_confirmation'))
            ->modalDescription(__('core::core.hard_delete_bulk_description'))
            ->action(function (Collection $records, $livewire) {
                $records->each->forceDelete();

                $livewire->resetTable();
            })
            ->deselectRecordsAfterCompletion()
            ->visible(
                fn ($livewire) => isset($livewire->activeTab)
                && in_array($livewire->activeTab, ['trash', 'deleted']))
            ->requiresConfirmation();
    }

    public static function getBulkActions(): array
    {
        $actions = [];

        if (static::enableRestore()) {
            $actions[] = static::getRestoreBulkAction();
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteBulkAction();
        }

        if (static::enableHardDelete()) {
            $actions[] = static::getHardDeleteBulkAction();
        }

        return $actions;
    }

    public static function getSoftDeleteEditAction(): FormAction
    {
        return FormAction::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->keyBindings(['command+e', 'ctrl+e'])
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(
                fn ($livewire, $record) => $livewire instanceof ViewRecord
                && method_exists($record, 'trashed')
                && ! $record->trashed());
    }

    public static function getFormActions(): Actions
    {
        $actions = [
            static::getSaveAction(),
            static::getCancelAction(),
        ];

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction();
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction();
        }

        if (static::enableRestore()) {
            $actions[] = static::getRestoreAction();
        }

        if (static::enableEdit()) {
            $actions[] = static::getSoftDeleteEditAction();
        }

        return Actions::make($actions);
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
