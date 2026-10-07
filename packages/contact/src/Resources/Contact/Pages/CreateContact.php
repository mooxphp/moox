<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\Contact\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactResource;

class CreateContact extends CreateRecord
{
    protected static string $resource = ContactResource::class;
}
