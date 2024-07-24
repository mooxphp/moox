<?php

namespace Moox\Press\Resources\WpSchulungResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpSchulungResource;

class ListPage extends ListRecords
{
    protected static string $resource = WpSchulungResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=schulung')];
    }
}
