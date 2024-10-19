<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;

class ListTags extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = TagResource::class;

    public function mount(): void
    {
        parent::mount();
        static::getResource()::setCurrentTab($this->activeTab);
    }

    // Correct method signature for Filament 3.2
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
                ->using(function (array $data, string $model): Tag {
                    return $model::create($data);
                })
                ->hidden(fn () => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $trashedCount = Tag::onlyTrashed()->count();
                    Tag::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.tags_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->activeTab === 'deleted' && Tag::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        return config('tag.resources.tag.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('tag.resources.tag.tabs', Tag::class);
    }
}
