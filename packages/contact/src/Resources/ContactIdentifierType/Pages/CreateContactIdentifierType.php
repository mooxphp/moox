<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierType\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactIdentifierTypeResource;

class CreateContactIdentifierType extends CreateRecord
{
    protected static string $resource = ContactIdentifierTypeResource::class;
}
