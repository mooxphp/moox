<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Localization\Models\Localization;
use Override;

abstract class BaseCreateDraft extends CreateRecord
{
    use CanResolveResourceClass;

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

        // Set the default locale before saving
        $record->setDefaultLocale($this->lang);

        // Get translatable and non-translatable attributes
        $translatableAttributes = property_exists($record, 'translatedAttributes')
            ? $record->translatedAttributes
            : [];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        // Fill and save the main record with non-translatable data
        $record->fill($nonTranslatableData);
        $record->save();
        // Create the translation if the model supports translations
        /** @var Model $translation */
        $translation = $record->translations()->firstOrNew([
            'locale' => $this->lang,
        ]);

        // Set translation data
        foreach ($translatableAttributes as $attr) {
            if (isset($translationData[$attr])) {
                $translation->setAttribute($attr, $translationData[$attr]);
            }
        }

        // Set author ID for the translation if the property exists
        if (property_exists($translation, 'author_id')) {
            $translation->author_id = auth()->id();
        }

        // Save the translation
        $record->translations()->save($translation);

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
        $localizations = Localization::with('language')->get();

        return [
            ActionGroup::make(
                $localizations->filter(fn($localization) => $localization->language->alpha2 !== $this->lang)
                    ->map(
                        fn($localization) => Action::make('language_' . $localization->language->alpha2)
                            ->icon('flag-' . $localization->language->alpha2)
                            ->label($localization->language->native_name ?? $localization->language->common_name)
                            ->color('gray')
                            ->url(fn() => $this->getResource()::getUrl('create', ['lang' => $localization->language->alpha2]))
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
