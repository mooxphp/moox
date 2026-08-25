<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\MailOutbox\Resources\MailTemplateResource;

class EditMailTemplate extends BaseEditRecord
{
    protected static string $resource = MailTemplateResource::class;
}
