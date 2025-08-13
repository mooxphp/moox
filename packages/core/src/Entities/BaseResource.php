<?php

namespace Moox\Core\Entities;

use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

abstract class BaseResource extends Resource
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
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->hidden(fn ($livewire) => $livewire->activeTab === 'deleted');
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
            ->visible(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']))
            ->action(function ($records): void {
                foreach ($records as $record) {
                    $record->unsetEventDispatcher();

                    \DB::table($record->getTable())
                        ->where('id', $record->id)
                        ->update(['deleted_at' => null]);

                    if (method_exists($record, 'translations')) {
                        $translations = $record->translations()->withTrashed()->get();
                        foreach ($translations as $translation) {
                            if ($translation->trashed()) {
                                $translation->restored_at = now();
                                $translation->deleted_by_id = null;
                                $translation->deleted_by_type = null;
                                if (auth()->check()) {
                                    $translation->restored_by_id = auth()->id();
                                    $translation->restored_by_type = auth()->user()::class;
                                }
                                $translation->restore();
                            }
                        }
                    }

                    $record->setEventDispatcher(app('events'));
                }
            });
    }

    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->action(function ($records, $livewire): void {
                if (auth()->check()) {
                    foreach ($records as $record) {
                        $record->save();

                        if (method_exists($record, 'translations')) {
                            $translations = $record->translations()->withTrashed()->get();
                            foreach ($translations as $translation) {
                                $translation->deleted_by_id = auth()->id();
                                $translation->deleted_by_type = auth()->user()::class;

                                if (isset($translation->restored_at)) {
                                    $translation->restored_at = null;
                                }
                                if (isset($translation->restored_by_id)) {
                                    $translation->restored_by_id = null;
                                }
                                if (isset($translation->restored_by_type)) {
                                    $translation->restored_by_type = null;
                                }

                                $translation->save();
                            }
                        }
                    }
                }

                foreach ($records as $record) {
                    $record->delete();
                }
            })
            ->hidden(fn ($livewire): bool => isset($livewire->activeTab) && in_array($livewire->activeTab, ['trash', 'deleted']));
    }

    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->keyBindings(['command+s', 'ctrl+s'])
            ->color('success')
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || $livewire instanceof EditRecord)
            ->hidden(fn ($livewire): bool => $livewire instanceof EditRecord
                && $livewire->record
                && method_exists($livewire->record, 'trashed')
                && $livewire->record->trashed());
    }

    public static function getPublishAction(): Action
    {
        return Action::make('publish')
            ->label(__('core::core.publish'))
            ->keyBindings(['command+p', 'ctrl+p'])
            ->color('secondary')
            ->action(function ($livewire): void {
                $livewire->data['translation_status'] = 'published';
                $livewire->save();

                if (method_exists($livewire->record, 'translateOrNew')) {
                    $locale = app()->getLocale();
                    $translation = $livewire->record->translateOrNew($locale);
                    $translation->published_at = now();
                    $translation->published_by_id = auth()->id();
                    $translation->published_by_type = auth()->user()::class;
                    $translation->to_publish_at = null;
                    $translation->unpublished_at = null;
                    $translation->to_unpublish_at = null;
                    $translation->save();
                }

                $livewire->redirect(static::getUrl('view', ['record' => $livewire->record]));
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || $livewire instanceof EditRecord)
            ->hidden(fn ($get, $livewire) => $get('translation_status') === 'published'
                || ($livewire instanceof EditRecord
                    && $livewire->record
                    && method_exists($livewire->record, 'trashed')
                    && $livewire->record->trashed()))
            ->requiresConfirmation()
            ->modalDescription(__('core::core.publish_modal_description'));
    }

    public static function getSaveAndCreateAnotherAction(): Action
    {
        return Action::make('saveAndCreateAnother')
            ->label(__('core::core.save_and_create_another'))
            ->color('secondary')
            ->button()
            ->action(function ($livewire): void {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
                $livewire->redirect(static::getUrl('create'));
            })
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord);
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('core::core.cancel'))
            ->keyBindings(['escape'])
            ->color('secondary')
            ->outlined()
            ->url(fn () => static::getUrl('index'));
        // ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord);
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('core::core.delete'))
            ->color('danger')
            ->outlined()
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->action(function ($livewire): void {
                if (auth()->check()) {
                    $livewire->record->deleted_by_id = auth()->id();
                    $livewire->record->deleted_by_type = auth()->user()::class;
                    $livewire->record->save();
                }

                $livewire->record->delete();
                $livewire->redirect(static::getUrl('index'));
            })
            ->keyBindings(['delete'])
            ->visible(fn ($livewire): bool => $livewire instanceof EditRecord)
            ->hidden(fn ($livewire): bool => $livewire instanceof EditRecord
                && $livewire->record
                && method_exists($livewire->record, 'trashed')
                && $livewire->record->trashed())
            ->requiresConfirmation();
    }

    public static function getEditAction(): Action
    {
        return Action::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->keyBindings(['command+e', 'ctrl+e'])
            ->url(fn ($record, $livewire) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire): bool => $livewire instanceof ViewRecord)
            ->hidden(fn ($livewire, $record): bool => $record && method_exists($record, 'trashed') && $record->trashed());
    }

    public static function getRestoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('core::core.restore'))
            ->color('success')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(fn ($record) => $record->restore())
            ->visible(fn ($livewire, $record): bool => $record && method_exists($record, 'trashed') && $record->trashed());
    }
}
