<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class EditDraft extends BaseEditDraft
{
   
    public ?string $lang = null;

    public function mount($record): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount($record);
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Model&TranslatableContract $record */
        $record = $this->getRecord();
        
        if (!property_exists($record, 'translatedAttributes')) {
            return $data;
        }
        
        $translatable = $record->translatedAttributes;
        $values = [];
        foreach ($translatable as $attr) {
            $values[$attr] = $record->$attr;
        }

        return $values;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!$this->lang) {
            return parent::handleRecordUpdate($record, $data);
        }

        /** @var Model&TranslatableContract $record */
        if (!property_exists($record, 'translatedAttributes')) {
            return parent::handleRecordUpdate($record, $data);
        }

        $translation = $record->translations()->firstOrNew([
            'locale' => $this->lang,
        ]);

        foreach ($record->translatedAttributes as $attr) {
            if (array_key_exists($attr, $data['translations'][$this->lang])) {
                $translation->setAttribute($attr, $data['translations'][$this->lang][$attr]);
            }
        }
        $translation->save();

        $record->update($data);

        return $record;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Model&TranslatableContract $model */
        $model = $this->getRecord();
        
        if (!property_exists($model, 'translatedAttributes')) {
            return $data;
        }

        $model->setDefaultLocale($this->lang);
        $translatedFields = $model->translatedAttributes;

        // Create translations array with translatable fields
        $data['translations'] = $data['translations'] ?? [];
        $data['translations'][$this->lang] = array_intersect_key($data, array_flip($translatedFields));

        // Move translated fields to translations array
        foreach ($translatedFields as $field) {
            if (isset($data[$field])) {
                $data['translations'][$this->lang][$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return $data;
    }
}
