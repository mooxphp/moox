<?php

namespace Moox\MooxPressWiki\Resources\MooxPressWikiResource\Pages;

use Filament\Actions\Action;
use Moox\Press\Models\WpPost;
use Moox\Core\Traits\HasDynamicTabs;
use Filament\Resources\Pages\ListRecords;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource;

class ListWpWikis extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = MooxPressWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=wiki')];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('moox-press-wiki.resources.wiki.tabs', WpPost::class);
    }
}
