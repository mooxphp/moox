<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\NestedTaxonomyResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\NestedTaxonomy;
use Moox\Builder\Resources\NestedTaxonomyResource;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListNestedTaxonomies extends ListRecords
{
    use TabsInListPage;

    public static string $resource = NestedTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): NestedTaxonomy {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = NestedTaxonomy::onlyTrashed()->count();
                    NestedTaxonomy::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && NestedTaxonomy::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('builder.resources.nested-taxonomy.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.nested-taxonomy.tabs', NestedTaxonomy::class);
    }
}
