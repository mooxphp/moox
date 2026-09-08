<?php

declare(strict_types=1);

namespace Moox\User\Resources\UserResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource;

class ListUsers extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = UserResource::class;

    public function getTabs(): array
    {
        if (! UserResource::canViewUserTabs()) {
            return [];
        }

        return $this->getDynamicTabs('user.resources.user.tabs', User::class);
    }
}
