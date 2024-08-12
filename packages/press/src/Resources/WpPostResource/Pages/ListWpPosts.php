<?php

namespace Moox\Press\Resources\WpPostResource\Pages;

use Filament\Actions\Action;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\Press\Resources\WpPostResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\WpPost;

class ListWpPosts extends ListRecords
{
    use HasDynamicTabs;

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
