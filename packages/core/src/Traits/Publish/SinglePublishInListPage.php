<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\Publish;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Moox\Draft\Models\Draft;

trait SinglePublishInListPage
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    /** @var class-string<Model> $model */
                    return $model::create($data);
                })
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    if (! class_exists(Draft::class)) {
                        return;
                    }

                    $trashedCount = Draft::onlyTrashed()->count();
                    Draft::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(function (): bool {
                    if ($this->activeTab !== 'deleted' || ! class_exists(Draft::class)) {
                        return false;
                    }

                    return Draft::onlyTrashed()->exists();
                }),
        ];
    }
}
