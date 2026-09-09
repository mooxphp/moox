<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\MailTemplate\Resources\MailTemplateResource;

class CreateMailTemplate extends BaseCreateDraft
{
    protected static string $resource = MailTemplateResource::class;
}
