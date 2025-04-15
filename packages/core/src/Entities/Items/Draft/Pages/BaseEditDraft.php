<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseEditDraft extends EditRecord
{
    use CanResolveResourceClass;

    public ?string $lang = null;

    protected function getFormActions(): array
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
        $translatable = $record->translatedAttributes;
        $values = [];
        foreach ($translatable as $attr) {
            $values[$attr] = $record->$attr;
        }

        return $values;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Model&TranslatableContract $record */
        if (! $this->lang || ! ($record instanceof TranslatableContract)) {
            return parent::handleRecordUpdate($record, $data);
        }

        if (! property_exists($record, 'translatedAttributes')) {
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

        if (! ($model instanceof TranslatableContract) || ! property_exists($model, 'translatedAttributes')) {
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
