<?php

namespace Moox\Audit\Resources\AuditResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Audit\Resources\AuditResource;
use Moox\Core\Traits\TabsInListPage;
use Spatie\Activitylog\Models\Activity;

class ListAudits extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = AuditResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('audit.resources.audit.tabs', Activity::class);
    }
}
