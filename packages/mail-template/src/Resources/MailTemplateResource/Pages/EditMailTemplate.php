<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\MailTemplate\Resources\MailTemplateResource;

class EditMailTemplate extends BaseEditDraft
{
    protected static string $resource = MailTemplateResource::class;
}
