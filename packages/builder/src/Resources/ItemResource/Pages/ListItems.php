<?php

declare(strict_types=1);

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

class ListItems extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = ItemResource::class;

    public function mount(): void
    {
        parent::mount();
        static::getResource()::setCurrentTab($this->activeTab);
    }

    // Correct method signature for Filament 3.2
    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
        $this->tableFilters = null;
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getTableQuery($this->activeTab);
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
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = Item::onlyTrashed()->count();
                    Item::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && Item::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('builder.resources.builder.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.builder.tabs', Item::class);
    }

    public function getHeaderWidgets(): array
    {
        return [
            ItemWidgets::class,
        ];
    }
}
