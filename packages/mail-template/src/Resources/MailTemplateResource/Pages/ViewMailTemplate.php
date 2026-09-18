<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailTemplateResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\MailTemplate\Resources\MailTemplateResource;

class ViewMailTemplate extends BaseViewDraft
{
    protected static string $resource = MailTemplateResource::class;
}
