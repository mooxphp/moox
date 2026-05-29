<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\Contact\Pages;

use Moox\Contact\Resources\ContactResource;
use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;

class CreateContact extends BaseCreateRecord
{
    protected static string $resource = ContactResource::class;
}
