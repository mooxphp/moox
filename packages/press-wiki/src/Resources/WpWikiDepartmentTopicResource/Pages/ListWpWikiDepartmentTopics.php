<?php

namespace Moox\PressWiki\Resources\WpWikiDepartmentTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressWiki\Models\WpWikiDepartmentTopic;
use Moox\PressWiki\Resources\WpWikiDepartmentTopicResource;

class ListWpWikiDepartmentTopics extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpWikiDepartmentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki-deparment.tabs', WpWikiDepartmentTopic::class);
    }
}
