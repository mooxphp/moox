<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Moox\Localization\Models\Localization;
use Override;

abstract class BaseCreateStaticRecord extends CreateRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    public function mount(): void
    {
        $defaultLocalization = Localization::query()->where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();

        $this->lang = request()->query('lang', $defaultLang);
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        /** @var Model&TranslatableContract $record */
        $record = new $model;

        $record->setDefaultLocale(BaseStaticModel::resolveTranslationLocale((string) $this->lang));

        $translatableAttributes = property_exists($record, 'translatedAttributes')
            ? $record->translatedAttributes
            : [];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        $record->fill($nonTranslatableData);
        ScopedResourceContext::applyDefaults($record, static::getResource());
        $record->save();

        $translationLocale = BaseStaticModel::resolveTranslationLocale((string) $this->lang);

        /** @var Model $translation */
        $translation = $record->translations()->firstOrNew([
            'locale' => $translationLocale,
        ]);

        foreach ($translatableAttributes as $attr) {
            if (array_key_exists($attr, $translationData)) {
                $translation->setAttribute($attr, $translationData[$attr]);
            }
        }

        $record->translations()->save($translation);
        $this->saveTaxonomyDataForRecord($record, $data);

        return $record;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('language_selector')
                ->view('localization::lang-selector')
                ->extraAttributes(['style' => 'margin-left: -8px;']),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $this->handleTaxonomiesBeforeFill($data);

        return $data;
    }
}
