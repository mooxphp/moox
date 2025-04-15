<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseViewDraft extends ViewRecord
{
    use CanResolveResourceClass;

    public ?string $lang = null;

    public function getFormActions(): array
    {
        return [];
    }

    public function mount($record): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount($record);
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! method_exists($record, 'getTranslation') || ! property_exists($record, 'translatedAttributes')) {
            return $data;
        }

        $translatable = $record->translatedAttributes;
        $values = [];
        foreach ($translatable as $attr) {
            $translation = $record->getTranslation($this->lang, false);
            $values[$attr] = $translation ? $translation->$attr : $record->$attr;
        }

        return $values;
    }
}
