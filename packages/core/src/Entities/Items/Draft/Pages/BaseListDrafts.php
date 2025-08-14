<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\CanResolveResourceClass;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

abstract class BaseListDrafts extends ListRecords
{
    use CanResolveResourceClass;

    /**
     * Get header actions for the list page
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn(array $data, string $model): Model => $model::create($data))
                ->hidden(fn(): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $model = $this->getModel();
                    $trashedCount = $model::onlyTrashed()->count();
                    $model::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn(): bool => $this->activeTab === 'deleted' && $this->getModel()::onlyTrashed()->exists()),
        ];
    }
}
