<?php

namespace Moox\Press\Resources\WpCommentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpComment;
use Moox\Press\Resources\WpCommentResource;

class ListWpComments extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.comment.tabs', WpComment::class);
    }
}
