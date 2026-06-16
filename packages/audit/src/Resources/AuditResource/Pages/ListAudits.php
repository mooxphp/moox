<?php

namespace Moox\Audit\Resources\AuditResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Audit\Models\Activity;
use Moox\Audit\Resources\AuditResource;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListAudits extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = AuditResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('audit.resources.audit.tabs', Activity::class);
    }
}
