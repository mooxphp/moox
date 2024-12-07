<?php

namespace Moox\Core\Traits\SoftDelete;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\TableQueryTrait;

trait SingleSoftDeleteInResource
{
    use TableQueryTrait;

    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->keyBindings(['command+s', 'ctrl+s'])
            ->color('success')
            ->action(function ($livewire) {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
            })
            ->visible(fn ($livewire) => $livewire instanceof CreateRecord || $livewire instanceof EditRecord);
    }

    public static function getSaveAndCreateAnotherAction(): Action
    {
        return Action::make('saveAndCreateAnother')
            ->label(__('core::core.save_and_create_another'))
            ->color('secondary')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire) {
                $livewire instanceof CreateRecord ? $livewire->create() : $livewire->save();
                $livewire->redirect(static::getUrl('create'));
            })
            ->visible(fn ($livewire) => $livewire instanceof CreateRecord);
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
            ->visible(fn ($livewire) => $livewire instanceof CreateRecord);
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('core::core.delete'))
            ->color('danger')
            ->outlined()
            ->extraAttributes(attributes: ['class' => 'w-full'])
            ->action(function ($livewire) {
                $livewire->record->delete();
                $livewire->redirect(static::getUrl('index'));
            })
            ->keyBindings(['delete'])
            ->visible(fn ($livewire) => $livewire instanceof EditRecord)
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
            ->visible(fn ($livewire) => $livewire instanceof ViewRecord);
    }

    public static function getSimpleFormActions(): Actions
    {
        return Actions::make([
            static::getSaveAction(),
            static::getSaveAndCreateAnotherAction(),
            static::getCancelAction(),
            static::getDeleteAction(),
            static::getEditAction(),
        ]);
    }

    protected static function applySoftDeleteQuery(Builder $query): Builder
    {
        $currentTab = request()->query('tab');
        $model = static::getModel();
        $modelInstance = new $model;

        if (! method_exists($modelInstance, 'getQualifiedDeletedAtColumn')) {
            return $query;
        }

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            return $query->whereNotNull($modelInstance->getQualifiedDeletedAtColumn());
        }

        return $query->whereNull($modelInstance->getQualifiedDeletedAtColumn());
    }
}
