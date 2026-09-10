<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailLayoutResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Resources\MailLayoutResource;

class ListMailLayouts extends BaseListDrafts
{
    use HasListPageTabs;

    public static string $resource = MailLayoutResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('mail-template.resources.mail-layout.tabs', MailLayout::class);
    }
}
