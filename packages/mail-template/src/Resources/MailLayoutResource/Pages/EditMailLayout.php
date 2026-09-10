<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailLayoutResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Moox\MailTemplate\Resources\MailLayoutResource;

class EditMailLayout extends BaseEditDraft
{
    protected static string $resource = MailLayoutResource::class;
}
