<?php

namespace Moox\PressWiki\Resources\WpWikiCompanyTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\PressWiki\Models\WpWikiCompanyTopic;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource;

class ListWpWikiCompanyTopics extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpWikiCompanyTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki-company.tabs', WpWikiCompanyTopic::class);
    }
}
