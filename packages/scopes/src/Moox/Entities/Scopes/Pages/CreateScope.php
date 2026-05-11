<?php

declare(strict_types=1);

namespace Moox\Scopes\Moox\Entities\Scopes\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Scopes\Moox\Entities\Scopes\ScopeResource;

class CreateScope extends CreateRecord
{
    protected static string $resource = ScopeResource::class;
}
