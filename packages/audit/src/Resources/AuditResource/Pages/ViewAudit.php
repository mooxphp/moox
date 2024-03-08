<?php

namespace Moox\Audit\Resources\AuditResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Audit\Resources\AuditResource;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
