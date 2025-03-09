<?php

namespace Moox\Press\Resources\WpCommentMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Press\Models\WpCommentMeta;
use Moox\Press\Resources\WpCommentMetaResource;

class ListWpCommentMetas extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpCommentMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press.resources.commentMeta.tabs', WpCommentMeta::class);
    }
}
