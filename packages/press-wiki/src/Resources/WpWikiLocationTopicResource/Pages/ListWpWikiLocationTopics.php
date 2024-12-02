<?php

namespace Moox\PressWiki\Resources\WpWikiLocationTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInListPage;
use Moox\PressWiki\Models\WpWikiLocationTopic;
use Moox\PressWiki\Resources\WpWikiLocationTopicResource;

class ListWpWikiLocationTopics extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpWikiLocationTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki-location.tabs', WpWikiLocationTopic::class);
    }
}
