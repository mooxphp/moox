<?php

namespace Moox\Sync\Resources\PlatformResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Sync\Models\Platform;
use Moox\Sync\Resources\PlatformResource;

class ListPlatforms extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = PlatformResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('sync::translations.platforms');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Platform {
                    return $model::create($data);
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('sync.resources.platform.tabs', Platform::class);
    }
}
