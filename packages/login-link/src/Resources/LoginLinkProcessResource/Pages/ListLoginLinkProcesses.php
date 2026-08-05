<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Resources\LoginLinkProcessResource;

class ListLoginLinkProcesses extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = LoginLinkProcessResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('login-link.resources.process.tabs', LoginLinkProcess::class);
    }
}
