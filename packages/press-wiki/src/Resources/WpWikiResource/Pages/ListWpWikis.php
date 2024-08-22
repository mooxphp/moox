<?php

namespace Moox\PressWiki\Resources\WpWikiResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressWiki\Models\WpWiki;
use Moox\PressWiki\Resources\WpWikiResource;

class ListWpWikis extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=wiki')];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-wiki.resources.wiki.tabs', WpWiki::class);
    }
}
