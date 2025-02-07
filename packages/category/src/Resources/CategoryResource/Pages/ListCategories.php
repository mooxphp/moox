<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Override;

class ListCategories extends ListRecords
{
    use TabsInListPage;

    public static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Category => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $trashedCount = Category::onlyTrashed()->count();
                    Category::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.categories_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->activeTab === 'deleted' && Category::onlyTrashed()->exists()),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return config('category.resources.category.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('category.resources.category.tabs', Category::class);
    }
}
