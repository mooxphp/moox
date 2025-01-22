<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\SyncResource;
use Override;

class ListSyncs extends ListRecords
{
    use TabsInListPage;

    public static string $resource = SyncResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('sync::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Sync => $model::create($data)),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('sync.resources.sync.tabs', Sync::class);
    }
}
