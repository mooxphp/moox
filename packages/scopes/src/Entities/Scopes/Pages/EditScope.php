<?php

declare(strict_types=1);

namespace Moox\Scopes\Entities\Scopes\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class EditScope extends EditRecord
{
    protected static string $resource = ScopeResource::class;
}
