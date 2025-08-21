<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;
use Override;

class ListTags extends BaseListDrafts
{
    use HasListPageTabs;

    public static string $resource = TagResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('tag.resources.tag.tabs', Tag::class);
    }
}
