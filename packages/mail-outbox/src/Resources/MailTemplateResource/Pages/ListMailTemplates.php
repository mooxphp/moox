<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\MailOutbox\Resources\MailTemplateResource;

class ListMailTemplates extends BaseListRecords
{
    protected static string $resource = MailTemplateResource::class;
}
