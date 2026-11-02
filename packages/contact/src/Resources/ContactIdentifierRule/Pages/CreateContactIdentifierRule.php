<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierRule\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactIdentifierRuleResource;

class CreateContactIdentifierRule extends CreateRecord
{
    protected static string $resource = ContactIdentifierRuleResource::class;
}
