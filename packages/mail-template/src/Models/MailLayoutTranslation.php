<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

/**
 * @property string $locale
 * @property string|null $title
 * @property string|null $footer
 */
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
