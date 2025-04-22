<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Actions\RestoreAction;
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

    public function getHeaderActions(): array
    {
        $localizations = \Moox\Localization\Models\Localization::with('language')->get();

        return [
            \Filament\Actions\ActionGroup::make(
                $localizations->map(fn ($localization) => \Filament\Actions\Action::make('language_'.$localization->language->alpha2)
                    ->icon('flag-'.$localization->language->alpha2)
                    ->label('')
                    ->color('transparent')
                    ->extraAttributes(['class' => 'bg-transparent hover:bg-transparent flex items-center gap-1'])
                    ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $localization->language->alpha2]))
                )
                    ->toArray()
            )
                ->color('transparent')
                ->label('Language')
                ->icon('flag-'.$this->lang)
                ->extraAttributes(['class' => '']),
              
            RestoreAction::make(),
                    
                
        ];
    }
}
