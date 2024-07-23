<?php

namespace Moox\Press\Resources\WpPostResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpPostResource;

class ListWpPosts extends ListRecords
{
    protected static string $resource = WpPostResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php')];
    }
}
