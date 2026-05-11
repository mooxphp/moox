<?php

declare(strict_types=1);

namespace Moox\Scopes\Moox\Entities\Scopes\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Scopes\Moox\Entities\Scopes\ScopeResource;

class EditScope extends EditRecord
{
    protected static string $resource = ScopeResource::class;
}
