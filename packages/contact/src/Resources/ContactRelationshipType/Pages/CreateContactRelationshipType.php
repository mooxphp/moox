<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationshipType\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactRelationshipTypeResource;

class CreateContactRelationshipType extends CreateRecord
{
    protected static string $resource = ContactRelationshipTypeResource::class;
}
