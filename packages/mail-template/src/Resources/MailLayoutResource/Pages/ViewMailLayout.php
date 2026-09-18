<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailLayoutResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\MailTemplate\Resources\MailLayoutResource;

class ViewMailLayout extends BaseViewDraft
{
    protected static string $resource = MailLayoutResource::class;
}
