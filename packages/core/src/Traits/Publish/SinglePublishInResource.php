<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Publish;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Resources\FullItemResource\Pages\CreateFullItem;
use Moox\Builder\Resources\ItemResource\Pages\CreateItem;
use Moox\Builder\Resources\ItemResource\Pages\EditItem;
use Moox\Builder\Resources\ItemResource\Pages\ViewItem;

trait SinglePublishInResource
{
    public static function getEditTableAction(): EditAction
    {
        return EditAction::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->keyBindings(['command+e', 'ctrl+e'])
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $record && ! $record->trashed());
    }

    public static function getViewTableAction(): ViewAction
    {
        return ViewAction::make('view')
            ->label(__('core::core.view'))
            ->color('secondary')
            ->keyBindings(['command+v', 'ctrl+v'])
            ->url(fn ($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getTableActions(): array
    {
        return [
            static::getEditTableAction(),
            static::getViewTableAction(),
        ];
    }

    public static function getRestoreBulkAction(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
            ->visible(fn ($livewire) => $livewire instanceof ViewRecord);
    }

    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->visible(fn ($livewire) => $livewire instanceof ViewRecord);
    }

    public static function getBulkActions(): array
    {
        return [
            static::getRestoreBulkAction(),
            static::getDeleteBulkAction(),
        ];
    }

    public static function getStatusTableColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('core::core.status'))
            ->alignment('center')
            ->badge()
            ->formatStateUsing(fn (string $state): string => strtoupper($state))
            ->color(fn (string $state): string => match ($state) {
                'draft' => 'primary',
                'published' => 'success',
                'scheduled' => 'info',
                'deleted' => 'danger',
                default => 'secondary',
            })
            ->toggleable()
            ->sortable();
    }

    public static function getPublishAtFormField(): DateTimePicker
    {
        return DateTimePicker::make('publish_at')
            ->label(__('core::core.publish_at'));
    }

    public static function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->options([
                    'draft' => __('core::core.draft'),
                    'scheduled' => __('core::core.scheduled'),
                    'published' => __('core::core.published'),
                ])
                ->label(__('core::core.status'))
                ->query(function (Builder $query, array $data) {
                    $status = $data['value'];
                    if ($status) {
                        static::applyStatusFilter($query, $status);
                    }
                }),
        ];
    }

    public static function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status) {
            switch ($status) {
                case 'draft':
                    $query->whereNull('publish_at');
                    break;
                case 'scheduled':
                    $query->where('publish_at', '>', now());
                    break;
                case 'published':
                    $query->where('publish_at', '<=', now());
                    break;
            }
        }
    }

    public static function getPublishAction(): Action
    {
        return Action::make('publish')
            ->label(__('core::core.publish'))
            ->color('success')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire) {
                $data = $livewire->form->getState();
                if (! $data['publish_at']) {
                    $data['publish_at'] = now();
                }
                $livewire->form->fill($data);
                $livewire instanceof CreateFullItem ? $livewire->create() : $livewire->save();
            })
            ->hidden(fn ($livewire, $record) => $record && $record->trashed());
    }

    public static function getSaveAction(): Action
    {
        return Action::make('save')
            ->label(__('core::core.save'))
            ->color('primary')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire) {
                $livewire instanceof CreateItem ? $livewire->create() : $livewire->save();
            })
            ->visible(fn ($livewire) => $livewire instanceof CreateItem || $livewire instanceof EditItem);
    }

    public static function getRestoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('core::core.restore'))
            ->color('success')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(fn ($record) => $record->restore())
            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewItem);
    }

    public static function getSaveAndCreateAnotherAction(): Action
    {
        return Action::make('saveAndCreateAnother')
            ->label(__('core::core.save_and_create_another'))
            ->color('secondary')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->action(function ($livewire) {
                $livewire->saveAndCreateAnother();
            })
            ->visible(fn ($livewire) => $livewire instanceof CreateItem);
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('core::core.cancel'))
            ->color('secondary')
            ->outlined()
            ->extraAttributes(['class' => 'w-full'])
            ->url(fn () => static::getUrl('index'))
            ->visible(fn ($livewire) => $livewire instanceof CreateItem);
    }

    public static function getEditAction(): Action
    {
        return Action::make('edit')
            ->label(__('core::core.edit'))
            ->color('primary')
            ->button()
            ->extraAttributes(['class' => 'w-full'])
            ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->visible(fn ($livewire, $record) => $livewire instanceof ViewItem && ! $record->trashed());
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('core::core.delete'))
            ->color('danger')
            ->link()
            ->extraAttributes(['class' => 'w-full'])
            ->action(fn ($record) => $record->delete())
            ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditItem);
    }

    public static function getFormActions(): Actions
    {
        return Actions::make([
            static::getPublishAction(),
            static::getSaveAction(),
            static::getRestoreAction(),
            static::getSaveAndCreateAnotherAction(),
            static::getCancelAction(),
            static::getEditAction(),
            static::getDeleteAction(),
        ]);
    }
}
