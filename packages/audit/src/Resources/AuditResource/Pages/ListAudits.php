<?php

namespace Moox\Audit\Resources\AuditResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Audit\Resources\AuditResource;

class ListAudits extends ListRecords
{
    protected static string $resource = AuditResource::class;
}
