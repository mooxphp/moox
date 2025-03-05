<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;
use Override;

class ListTags extends ListRecords
{
    use TabsInListPage;

    public static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Tag => $model::create($data))
                ->hidden(fn (): bool => $this->activeTab === 'deleted'),
            Action::make('emptyTrash')
                ->label(__('core::core.empty_trash'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function (): void {
                    $trashedCount = Tag::onlyTrashed()->count();
                    Tag::onlyTrashed()->forceDelete();
                    Notification::make()
                        ->title(__('core::core.trash_emptied_successfully'))
                        ->body(trans_choice('core::core.tags_permanently_deleted', $trashedCount, ['count' => $trashedCount]))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->activeTab === 'deleted' && Tag::onlyTrashed()->exists()),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return config('tag.resources.tag.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('tag.resources.tag.tabs', Tag::class);
    }
}
