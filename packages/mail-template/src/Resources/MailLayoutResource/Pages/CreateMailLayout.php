<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailLayoutResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\MailTemplate\Resources\MailLayoutResource;

class CreateMailLayout extends BaseCreateDraft
{
    protected static string $resource = MailLayoutResource::class;
}
