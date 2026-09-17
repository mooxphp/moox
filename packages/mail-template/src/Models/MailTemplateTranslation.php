<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Core\Enums\TranslationStatus;

/**
 * @property string $locale
 * @property string|null $title
 * @property string|null $mail_content
 * @property string|null $footer
 * @property TranslationStatus|null $translation_status
 */
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

    /**
     * @return array<string, mixed>
     */
    protected function getCustomCasts(): array
    {
        return [
            'translation_status' => TranslationStatus::class,
        ];
    }
}
