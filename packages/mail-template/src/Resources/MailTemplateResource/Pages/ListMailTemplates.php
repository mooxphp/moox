<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\MailTemplate\Resources\MailTemplateResource;

class ListMailTemplates extends BaseListRecords
{
    protected static string $resource = MailTemplateResource::class;
}
