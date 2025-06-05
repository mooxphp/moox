<?php

namespace Moox\Packages\Moox\Entities\Packages\Package\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Packages\Moox\Entities\Packages\Package\PackagesResource;

class ListPackages extends ListRecords
{
    protected static string $resource = PackagesResource::class;
}
