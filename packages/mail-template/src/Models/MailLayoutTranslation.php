<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models;

use Illuminate\Support\Facades\Storage;
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
            'logo',
            'footer',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! filled($this->logo)) {
            return null;
        }

        $path = (string) $this->logo;

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
