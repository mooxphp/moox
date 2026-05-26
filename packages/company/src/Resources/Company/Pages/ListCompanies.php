<?php

declare(strict_types=1);

namespace Moox\Company\Resources\Company\Pages;

use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListCompanies extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = CompanyResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('company.resources.company.tabs', Company::class);
    }
}
