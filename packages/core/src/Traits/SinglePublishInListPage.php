<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Moox\Builder\Models\Item;

trait SinglePublishInListPage
{
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
}
