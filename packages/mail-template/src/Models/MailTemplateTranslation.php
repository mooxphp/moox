<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class MailTemplateTranslation extends BaseDraftTranslationModel
{
    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'mail_template_id',
            'title',
            'mail_content',
            'footer',
        ];
    }
}
