<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\LoginLink\Resources\LoginLinkProcessResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = LoginLinkProcessResource::class;
}
