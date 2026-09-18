<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Resources\MailLayoutResource\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\MailTemplate\Resources\MailLayoutResource;
use Override;

class ViewMailLayout extends BaseViewDraft
{
    protected static string $resource = MailLayoutResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    public function mutateFormDataBeforeFill(array $data): array
    {
        $values = parent::mutateFormDataBeforeFill($data);
        $record = $this->getRecord();

        if (! method_exists($record, 'translations') || ! property_exists($record, 'translatedAttributes')) {
            return $values;
        }

        $translation = $record->translations()->withTrashed()->where('locale', $this->lang)->first();

        foreach ($record->translatedAttributes as $attr) {
            $values[$attr] = $translation?->$attr;
        }

        return $values;
    }
}
