<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifier\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactIdentifierResource;

class CreateContactIdentifier extends CreateRecord
{
    protected static string $resource = ContactIdentifierResource::class;
}
