<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Resources\FullItemResource\Pages\CreateFullItem;

trait StatusInResource
{
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

    public static function getStatusFilters(): array
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
}
