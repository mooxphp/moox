<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Moox\Sync\Models\Sync;
use Filament\Actions\CreateAction;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Sync\Resources\SyncResource;
use Filament\Resources\Pages\ListRecords;

class ListSyncs extends ListRecords
{
    use HasDynamicTabs;

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
        return $this->getDynamicTabs('sync.sync.tabs', Sync::class);
    }
}
