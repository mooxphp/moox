<?php

namespace Moox\Core\Entities;

use Filament\Forms\Components\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class MooxBaseResource extends Resource
{
    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        if (method_exists(static::class, 'addTaxonomyRelationsToQuery')) {
            $query = static::addTaxonomyRelationsToQuery($query);
        }

        return $query;
    }

    public static function getEloquentQuery(): Builder
    {
        $model = static::getModel();
        $query = parent::getEloquentQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if (method_exists(static::class, 'applySoftDeleteQuery')) {
            $query = static::applySoftDeleteQuery($query);
        }

        if (($currentTab = request()->query('tab')) && method_exists(static::class, 'applyTabQuery')) {
            $query = static::applyTabQuery($query, $currentTab);
        }

        $methods = array_filter(get_class_methods(static::class), fn ($method): bool => str_ends_with($method, 'ModifyTableQuery')
            && ! in_array($method, ['applySoftDeleteQuery', 'applyTabQuery']));

        foreach ($methods as $method) {
            $query = static::$method($query);
        }

        return static::modifyEloquentQuery($query);
    }

    public static function getTableQuery(): Builder
    {
        $model = static::getModel();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $model::withTrashed();
        } else {
            $query = method_exists(parent::class, 'getTableQuery')
                ? parent::getTableQuery()
                : static::getModel()::query();
        }

        if (method_exists(static::class, 'applySoftDeleteQuery')) {
            $query = static::applySoftDeleteQuery($query);
        }

        if (($currentTab = request()->query('tab')) && method_exists(static::class, 'applyTabQuery')) {
            $query = static::applyTabQuery($query, $currentTab);
        }

        $methods = array_filter(get_class_methods(static::class), fn ($method): bool => str_ends_with($method, 'ModifyTableQuery')
            && ! in_array($method, ['applySoftDeleteQuery', 'applyTabQuery']));

        foreach ($methods as $method) {
            $query = static::$method($query);
        }

        return $query;
    }

    public static function getEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->color('primary')
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]));
    }

    public static function getViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->color('secondary')
            ->url(fn ($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getRestoreTableAction(): RestoreAction
    {
        return RestoreAction::make('restore');
    }

    public static function getRestoreBulkAction(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
            ->visible(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']));
    }

    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->hidden(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']));
    }

    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->keyBindings(['command+s', 'ctrl+s'])
            ->color('success')
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || $livewire instanceof EditRecord);
    }

    public static function getSaveAndCreateAnotherAction(): Action
    {
        return Action::make('saveAndCreateAnother')
            ->label(__('core::core.save_and_create_another'))
            ->color('secondary')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
                $livewire->redirect(static::getUrl('create'));
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord);
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->label(__('core::core.cancel'))
            ->keyBindings(['escape'])
            ->color('secondary')
            ->outlined()
            ->url(fn () => static::getUrl('index'))
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord);
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('core::core.delete'))
            ->color('danger')
            ->outlined()
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->action(function ($livewire): void {
                $livewire->record->delete();
                $livewire->redirect(static::getUrl('index'));
            })
            ->keyBindings(['delete'])
            ->visible(fn ($livewire): bool => $livewire instanceof EditRecord)
            ->requiresConfirmation();
    }

    public static function getEditAction(): Action
    {
        return Action::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->keyBindings(['command+e', 'ctrl+e'])
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire): bool => $livewire instanceof ViewRecord);
    }

    public static function getRestoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('core::core.restore'))
            ->color('success')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(fn ($record) => $record->restore())
            ->visible(fn ($livewire, $record): bool => $record && $record->trashed());
    }
}
