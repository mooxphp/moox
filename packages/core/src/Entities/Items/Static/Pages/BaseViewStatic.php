<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Moox\Localization\Models\Localization;

abstract class BaseViewStatic extends ViewRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    public function getFormActions(): array
    {
        return [];
    }

    public function mount($record): void
    {
        $defaultLocalization = Localization::query()->where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();

        $this->lang = request()->query('lang', $defaultLang);
        parent::mount($record);

        if ($this->record && method_exists($this->record, 'translations')) {
            $localization = Localization::query()
                ->where('locale_variant', $this->lang)
                ->where('is_active_admin', true)
                ->first();

            if ($localization === null) {
                $fallback = Localization::query()->where('is_default', true)->first();

                if ($fallback !== null) {
                    $this->redirect($this->getResource()::getUrl('view', [
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
            $translationLocale = BaseStaticModel::resolveTranslationLocale((string) $this->lang);

            foreach ($record->translatedAttributes as $attr) {
                $translation = $record->translate($translationLocale, true);
                $data[$attr] = $translation?->getAttribute($attr);
            }
        }

        $this->handleTaxonomiesBeforeFill($data);

        return $data;
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
