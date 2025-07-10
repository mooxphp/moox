<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\Publish;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Item\Moox\Entities\Items\Item\Pages\CreateItem;

trait SinglePublishInResource
{
    public static function getTableActions(): array
    {
        return [
            static::getEditTableAction(),
            static::getViewTableAction(),
        ];
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
                ->query(function (Builder $query, array $data): void {
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
            ->action(function ($livewire): void {
                $data = $livewire->form->getState();
                if (! $data['publish_at']) {
                    $data['publish_at'] = now();
                }

                $livewire->form->fill($data);
                $livewire instanceof CreateItem ? $livewire->create() : $livewire->save();
            })
            ->hidden(fn ($livewire, $record): bool => $record && $record->trashed());
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
