<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Moox\Media\Resources\MediaCollectionResource;

class EditMediaCollection extends EditRecord
{
    protected static string $resource = MediaCollectionResource::class;

    public ?string $lang = null;

    public function mount($record): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount($record);
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $values = $data;

        if (! method_exists($record, 'getTranslation') || ! property_exists($record, 'translatedAttributes')) {
            return $values;
        }

        $translation = $record->getTranslation($this->lang, false);

        if (! $translation) {
            foreach ($record->translatedAttributes as $attribute) {
                $values[$attribute] = null;
            }

            return $values;
        }

        foreach ($record->translatedAttributes as $attribute) {
            $values[$attribute] = $translation->$attribute ?? null;
        }

        return $values;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        if (! method_exists($record, 'translateOrNew') || ! property_exists($record, 'translatedAttributes')) {
            return $data;
        }

        $translation = $record->translateOrNew($this->lang);

        foreach ($record->translatedAttributes as $attribute) {
            if (array_key_exists($attribute, $data)) {
                $translation->$attribute = $data[$attribute];
                unset($data[$attribute]);
            }
        }

        $translation->save();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('language_selector')
                ->view('localization::lang-selector')
                ->extraAttributes(['style' => 'margin-left: -8px;']),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }
}
