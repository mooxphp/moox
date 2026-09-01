<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\MailTemplate\Resources\MailTemplateResource;

class CreateMailTemplate extends BaseCreateRecord
{
    protected static string $resource = MailTemplateResource::class;
}
