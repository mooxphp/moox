<?php

namespace Moox\Sync\Resources\SyncResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Sync\Resources\SyncResource;

class CreateSync extends CreateRecord
{
    protected static string $resource = SyncResource::class;
}
