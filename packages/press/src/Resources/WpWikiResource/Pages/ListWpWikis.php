<?php

namespace Moox\Press\Resources\WpWikiResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpWikiResource;

class ListWpWikis extends ListRecords
{
    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=wiki')];
    }
}
