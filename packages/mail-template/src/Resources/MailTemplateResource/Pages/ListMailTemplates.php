<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseListDrafts;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Resources\MailTemplateResource;

class ListMailTemplates extends BaseListDrafts
{
    use HasListPageTabs;

    public static string $resource = MailTemplateResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('mail-template.resources.mail-template.tabs', MailTemplate::class);
    }
}
