<?php

namespace Moox\PressWiki\Resources\WpWikiLetterTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\PressWiki\Models\WpWikiLetterTopic;
use Moox\PressWiki\Resources\WpWikiLetterTopicResource;

class ListWpWikiLetterTopics extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpWikiLetterTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki-letter-topic.tabs', WpWikiLetterTopic::class);
    }
}
