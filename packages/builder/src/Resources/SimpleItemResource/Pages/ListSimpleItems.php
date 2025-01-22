<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleItemResource\Pages;

use Override;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\SimpleItem;
use Moox\Builder\Resources\SimpleItemResource;
use Moox\Core\Traits\Tabs\TabsInListPage;

class ListSimpleItems extends ListRecords
{
    use TabsInListPage;

    public static string $resource = SimpleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return config('builder.resources.simple-item.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.simple-item.tabs', SimpleItem::class);
    }
}
