<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Localization\Models\Localization;

/**
 * @phpstan-type TranslatableModel = Model&TranslatableContract
 *
 * @phpstan-property-read array<string> $translatedAttributes
 */
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
        $values = $data;

        if (! method_exists($record, 'getTranslation') || ! property_exists($record, 'translatedAttributes')) {
            return $values;
        }

        $translatable = $record->translatedAttributes;
        foreach ($translatable as $attr) {
            $translation = $record->getTranslation($this->lang, false);
            $values[$attr] = $translation ? $translation->$attr : $record->$attr;
        }

        return $values;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Model&TranslatableContract $record */
        if (! $this->lang) {
            return parent::handleRecordUpdate($record, $data);
        }

        if (! property_exists($record, 'translatedAttributes')) {
            return parent::handleRecordUpdate($record, $data);
        }

        // Save translation manually
        $translation = $record->translations()->firstOrNew([
            'locale' => $this->lang,
        ]);

        foreach ($record->translatedAttributes as $attr) {
            if (array_key_exists($attr, $data['translations'][$this->lang] ?? [])) {
                $translation->setAttribute($attr, $data['translations'][$this->lang][$attr]);
            }
        }
        $translation->save();

        // Remove 'translations' from data before update
        unset($data['translations']);

        $record->update($data);

        return $record;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Model&TranslatableContract $model */
        $model = $this->getRecord();

        if (! property_exists($model, 'translatedAttributes')) {
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

    public function getHeaderActions(): array
    {
        $languages = Localization::with('language')->get();
        $languageCodes = $languages->map(fn ($localization) => $localization->language->alpha2);

        return [
            ActionGroup::make(
                $languages->map(fn ($localization) => Action::make('language_'.$localization->language->alpha2)
                    ->icon('flag-'.$localization->language->alpha2)
                    ->label('')
                    ->color('transparent')
                    ->extraAttributes(['class' => 'bg-transparent hover:bg-transparent flex items-center gap-1'])
                    ->url(fn () => $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $localization->language->alpha2]))
                )
                    ->toArray()
            )
                ->color('transparent')
                ->label('Language')
                ->icon('flag-'.$this->lang)
                ->extraAttributes(['class' => '']),
        ];
    }
}
