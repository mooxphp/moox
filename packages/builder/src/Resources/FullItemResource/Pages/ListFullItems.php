<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FullItemResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\FullItem;
use Moox\Builder\Resources\FullItemResource;
use Moox\Builder\Resources\FullItemResource\Widgets\FullItemWidgets;
use Moox\Core\Traits\TabsInListPage;

class ListFullItems extends ListRecords
{
    use TabsInListPage;

    public static string $resource = FullItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): FullItem {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = FullItem::onlyTrashed()->count();
                    FullItem::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && FullItem::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('builder.resources.full-item.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.full-item.tabs', FullItem::class);
    }

    public function getHeaderWidgets(): array
    {
        return [
            FullItemWidgets::class,
        ];
    }
}
