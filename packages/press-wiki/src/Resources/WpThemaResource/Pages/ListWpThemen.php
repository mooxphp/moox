<?php

namespace Moox\PressWiki\Resources\WpThemaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressWiki\Resources\WpThemaResource;
use Moox\PressWiki\Models\WpThema;

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
        return $this->getDynamicTabs('press-wiki.resources.theme.tabs', WpThema::class);
    }
}
