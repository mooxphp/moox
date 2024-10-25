<?php

namespace Moox\Press\Resources\WpPostResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\TabsInPage;
use Moox\Press\Models\WpPost;
use Moox\Press\Resources\WpPostResource;

class ListWpPosts extends ListRecords
{
    use TabsInPage;

    protected static string $resource = WpPostResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php')];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.post.tabs', WpPost::class);
    }
}
