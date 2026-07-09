<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Moox\Localization\Models\Localization;

abstract class BaseEditStaticRecord extends EditRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    protected function getFormActions(): array
    {
        return [];
    }

    public function mount($record): void
    {
        $defaultLocalization = Localization::query()->where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();

        $this->lang = request()->query('lang', $defaultLang);
        parent::mount($record);

        if ($this->record instanceof Model && method_exists($this->record, 'translations')) {
            $localization = Localization::query()
                ->where('locale_variant', $this->lang)
                ->where('is_active_admin', true)
                ->first();

            if ($localization === null) {
                $fallback = Localization::query()->where('is_default', true)->first();
                if ($fallback !== null) {
                    $this->redirect($this->getResource()::getUrl('edit', [
                        'record' => $this->record,
                        'lang' => $fallback->locale_variant,
                    ]));
                } else {
                    $this->redirect($this->getResource()::getUrl('index'));
                }
            }
        }
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (property_exists($record, 'translatedAttributes')) {
            foreach ($record->translatedAttributes as $attr) {
                $translation = $record->getTranslation($this->lang, false);
                $data[$attr] = $translation !== null ? $translation->$attr : null;
            }
        }

        $this->handleTaxonomiesBeforeFill($data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $this->lang || ! property_exists($record, 'translatedAttributes')) {
            $record->update($data);
            $this->saveTaxonomyDataForRecord($record, $data);

            return $record;
        }

        /** @var Model&TranslatableContract $record */
        $translatable = $record->translatedAttributes;
        $translationData = array_intersect_key($data, array_flip($translatable));
        $baseData = array_diff_key($data, array_flip($translatable));

        $record->update($baseData);

        $translation = $record->translations()->firstOrNew(['locale' => $this->lang]);

        foreach ($translatable as $attr) {
            if (array_key_exists($attr, $translationData)) {
                $translation->setAttribute($attr, $translationData[$attr]);
            }
        }

        $record->translations()->save($translation);
        $this->saveTaxonomyDataForRecord($record, $data);

        return $record;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('language_selector')
                ->view('localization::lang-selector')
                ->extraAttributes(['style' => 'margin-left: -8px;']),
        ];
    }
}
