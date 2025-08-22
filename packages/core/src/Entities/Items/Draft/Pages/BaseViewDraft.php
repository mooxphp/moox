<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Override;

abstract class BaseViewDraft extends ViewRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    #[Override]
    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    protected function isRecordTrashed(): bool
    {
        if (! $this->record) {
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

        $this->handleTaxonomiesBeforeFill($values);

        return $values;
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
