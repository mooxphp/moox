<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource;
use Moox\Core\Traits\TabsInListPage;

class ListCategories extends ListRecords
{
    use TabsInListPage;

    public static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Category {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = Category::onlyTrashed()->count();
                    Category::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.categories_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && Category::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('category.resources.category.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('category.resources.category.tabs', Category::class);
    }
}
