<?php

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource;
use Moox\Builder\Resources\ItemResource\Widgets\ItemWidgets;
use Moox\Core\Traits\HasDynamicTabs;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = ItemResource::class;

    public function mount(): void
    {
        parent::mount();
        static::getResource()::setCurrentTab($this->activeTab);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Item {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('Empty Trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = Item::onlyTrashed()->count();
                    Item::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title('Trash emptied successfully')
                        ->body("{$trashedCount} items were permanently deleted.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && Item::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return __('core::builder.builder');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.builder.tabs', Item::class);
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getTableQuery($this->activeTab);
    }

    public function getHeaderWidgets(): array
    {
        return [
            ItemWidgets::class,
        ];
    }
}
