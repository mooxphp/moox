<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\Sync\Models\Sync;
use Moox\Sync\Resources\SyncResource;

class ListSyncs extends ListRecords
{
    use TabsInListPage;

    public static string $resource = SyncResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('sync::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Sync {
                    return $model::create($data);
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('sync.resources.sync.tabs', Sync::class);
    }
}
