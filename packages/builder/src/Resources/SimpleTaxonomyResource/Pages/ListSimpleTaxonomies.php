<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleTaxonomyResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Models\SimpleTaxonomy;
use Moox\Builder\Resources\SimpleTaxonomyResource;
use Moox\Core\Traits\TabsInPage;

class ListSimpleTaxonomies extends ListRecords
{
    use TabsInPage;

    public static string $resource = SimpleTaxonomyResource::class;

    public function mount(): void
    {
        parent::mount();
        static::getResource()::setCurrentTab($this->activeTab);
    }

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
                ->using(function (array $data, string $model): SimpleTaxonomy {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = SimpleTaxonomy::onlyTrashed()->count();
                    SimpleTaxonomy::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.items_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && SimpleTaxonomy::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('builder.resources.simple-taxonomy.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.simple-taxonomy.tabs', SimpleTaxonomy::class);
    }
}
