<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class MailLayoutTranslation extends BaseDraftTranslationModel
{
    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'mail_layout_id',
            'title',
            'footer',
        ];
    }
}
