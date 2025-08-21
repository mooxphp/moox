<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;

class ListTags extends BaseListDrafts
{
    use HasListPageTabs;

    public static string $resource = TagResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('tag.resources.tag.tabs', Tag::class);
    }
}
