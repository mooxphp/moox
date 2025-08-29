<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Override;

abstract class BaseCreateDraft extends CreateRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    public function mount(): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        /** @var Model&TranslatableContract $record */
        $record = new $model;

        $record->setDefaultLocale($this->lang);

        $translatableAttributes = property_exists($record, 'translatedAttributes')
            ? $record->translatedAttributes
            : [];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        $record->fill($nonTranslatableData);
        $record->save();
        /** @var Model $translation */
        $translation = $record->translations()->firstOrNew([
            'locale' => $this->lang,
        ]);

        foreach ($translatableAttributes as $attr) {
            if (isset($translationData[$attr])) {
                $translation->setAttribute($attr, $translationData[$attr]);
            }
        }

        $record->translations()->save($translation);

        // Save taxonomy data if available
        $this->saveTaxonomyDataForRecord($record, $data);

        return $record;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }

    protected function resolveRecord($key): Model
    {
        $model = static::getModel();

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->find($key) ?? $model::make();
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('language_selector')
                ->view('localization::lang-selector')
                ->extraAttributes(['style' => 'margin-left: -8px;']),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $this->handleTaxonomiesBeforeFill($data);

        return $data;
    }
}
