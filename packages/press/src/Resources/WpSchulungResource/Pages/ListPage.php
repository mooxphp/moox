<?php

namespace Moox\Press\Resources\WpSchulungResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Models\WpPost;
use Moox\Press\Resources\WpSchulungResource;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    protected static string $resource = WpSchulungResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=schulung')];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('expiry.resources.training.tabs', WpPost::class);
    }
}
