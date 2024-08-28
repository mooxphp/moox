<?php

namespace Moox\PressWiki\Resources\WpTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressWiki\Models\WpTopic;
use Moox\PressWiki\Resources\WpTopicResource;

class ListWpTopics extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.theme.tabs', WpTopic::class);
    }
}
