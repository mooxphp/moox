<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\Contact\Pages;

use Moox\Contact\Models\Contact;
use Moox\Contact\Resources\ContactResource;
use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;

class ListContacts extends BaseListRecords
{
    use HasListPageTabs;

    protected static string $resource = ContactResource::class;

    public function getTabs(): array
    {
        return $this->getDynamicTabs('contact.resources.contact.tabs', Contact::class);
    }
}
