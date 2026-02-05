<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Record\BaseRecordModel;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseListRecords extends ListRecords
{
    use CanResolveResourceClass;

    /**
     * Get header actions for the list page
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Model => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    /** @var class-string<BaseRecordModel> $model */
                    $model = $this->getModel();
                    $trashedCount = $model::onlyTrashed()->count();
                    $model::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index', ['tab' => 'all']));
                })
                ->requiresConfirmation()
                ->visible(function (): bool {
                    /** @var class-string<BaseRecordModel> $model */
                    $model = $this->getModel();

                    return $this->activeTab === 'deleted' && $model::onlyTrashed()->exists();
                }),
        ];
    }
}
