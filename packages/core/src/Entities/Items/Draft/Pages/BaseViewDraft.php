<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Localization\Models\Localization;
use Override;

abstract class BaseViewDraft extends ViewRecord
{
    use CanResolveResourceClass;

    public ?string $lang = null;

    #[Override]
    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title . ' - ' . __('core::core.deleted');
        }

        return $title;
    }

    protected function isRecordTrashed(): bool
    {
        if (!$this->record) {
            return false;
        }

        $currentLang = $this->lang ?? request()->query('lang') ?? app()->getLocale();

        if (method_exists($this->record, 'translations')) {
            $translation = $this->record->translations()->withTrashed()->where('locale', $currentLang)->first();

            return $translation && $translation->trashed();
        }

        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }

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
        $values = $data;

        if (method_exists($record, 'getTranslation') && property_exists($record, 'translatedAttributes')) {
            $translatable = $record->translatedAttributes;
            foreach ($translatable as $attr) {
                $translation = $record->getTranslation($this->lang, false);
                $values[$attr] = $translation ? $translation->$attr : $record->$attr;
            }
        }

        return $values;
    }

    public function getHeaderActions(): array
    {
        $localizations = Localization::with('language')->get();

        return [
            ActionGroup::make(
                $localizations->filter(fn($localization) => $localization->language->alpha2 !== $this->lang)
                    ->map(
                        fn($localization) => Action::make('language_' . $localization->language->alpha2)
                            ->icon('flag-' . $localization->language->alpha2)
                            ->label($localization->language->native_name ?? $localization->language->common_name)
                            ->color('gray')
                            ->url(fn() => $this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $localization->language->alpha2]))
                    )
                    ->all()
            )
                ->color('gray')
                ->label($localizations->firstWhere('language.alpha2', $this->lang)?->language->native_name ?? $localizations->firstWhere('language.alpha2', $this->lang)?->language->common_name)
                ->icon('flag-' . $this->lang)
                ->button()
                ->extraAttributes(['style' => 'border-radius: 8px; border: 1px solid #e5e7eb; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-left: -8px; min-width: 225px; justify-content: flex-start; padding: 10px 12px;']),
        ];
    }
}
