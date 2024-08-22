<?php

namespace Moox\MooxPressWiki\Resources\MooxPressThemaResource\Pages;


use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Models\WpPost;
use Moox\Press\Resources\WpThemaResource;

class ListWpThemen extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpThemaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.theme.tabs', WpPost::class);
    }
}
