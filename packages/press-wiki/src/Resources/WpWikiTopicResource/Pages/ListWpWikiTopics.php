<?php

namespace Moox\PressWiki\Resources\WpWikiTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressWiki\Models\WpWikiTopic;
use Moox\PressWiki\Resources\WpWikiTopicResource;

class ListWpWikiTopics extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpWikiTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki-topic.tabs', WpWikiTopic::class);
    }
}
