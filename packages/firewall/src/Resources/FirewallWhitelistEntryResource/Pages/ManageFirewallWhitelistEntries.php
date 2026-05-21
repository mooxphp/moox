<?php

namespace Moox\Firewall\Resources\FirewallWhitelistEntryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Moox\Firewall\Resources\FirewallWhitelistEntryResource;

class ManageFirewallWhitelistEntries extends ManageRecords
{
    protected static string $resource = FirewallWhitelistEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
