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
use Override;

class ListNestedTaxonomies extends ListRecords
{
    use TabsInListPage;

    public static string $resource = NestedTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): NestedTaxonomy => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $trashedCount = NestedTaxonomy::onlyTrashed()->count();
                    NestedTaxonomy::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->activeTab === 'deleted' && NestedTaxonomy::onlyTrashed()->exists()),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return config('builder.resources.nested-taxonomy.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.nested-taxonomy.tabs', NestedTaxonomy::class);
    }
}
