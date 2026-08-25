<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\MailOutbox\Resources\MailTemplateResource;

class CreateMailTemplate extends BaseCreateRecord
{
    protected static string $resource = MailTemplateResource::class;
}
