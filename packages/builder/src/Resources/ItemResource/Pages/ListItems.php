<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Override;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListItems extends ListRecords
{
    use TabsInListPage;

    public static string $resource = ItemResource::class;

    #[Override]
    public function mount(): void
    {
        parent::mount();
        $this->mountTabsInListPage();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn(array $data, string $model): Item => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $trashedCount = Item::onlyTrashed()->count();
                    Item::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->activeTab === 'deleted' && Item::onlyTrashed()->exists()),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return config('builder.resources.item.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.item.tabs', Item::class);
    }
}
