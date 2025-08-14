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

        if ($this->record && method_exists($this->record, 'trashed')) {
            $translation = $this->record->translations()->withTrashed()->where('locale', $this->lang)->first();

            if ($translation && $translation->trashed()) {
                $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $this->lang]));
            }
        }
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $values = $data;

        if (!method_exists($record, 'getTranslation') || !property_exists($record, 'translatedAttributes')) {
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
        if (!$this->lang) {
            return parent::handleRecordUpdate($record, $data);
        }

        if (!property_exists($record, 'translatedAttributes')) {
            return parent::handleRecordUpdate($record, $data);
        }

        $translationData = $data['translations'][$this->lang] ?? [];

        unset($data['translations']);

        $record->update($data);

        if (!empty($translationData)) {
            $relation = $record->translations();
            $translationModel = $relation->getRelated();
            $foreignKey = $relation->getForeignKeyName();

            $translation = $record->translations()
                ->where('locale', $this->lang)
                ->first();

            if (!$translation) {
                $translation = $record->translations()->make([
                    $relation->getForeignKeyName() => $record->id,
                    'locale' => $this->lang,
                ]);
            }

            foreach ($record->translatedAttributes as $attr) {
                if (array_key_exists($attr, $translationData)) {
                    $translation->setAttribute($attr, $translationData[$attr]);
                }
            }
            $translation->save();
        }

        return $record;
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Model&TranslatableContract $model */
        $model = $this->getRecord();

        if (!property_exists($model, 'translatedAttributes')) {
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

        $integerFields = [
            'author_id',
            'published_by_id',
            'unpublished_by_id',
            'deleted_by_id',
            'restored_by_id',
            'published_at',
            'unpublished_at',
            'deleted_at',
            'restored_at',
            'to_publish_at',
            'to_unpublish_at',
            'created_by_id',
            'updated_by_id',
        ];

        // Fields that should not be automatically set to null
        $protectedFields = [
            'published_at',
            'published_by_id',
            'published_by_type',
            'unpublished_at',
            'unpublished_by_id',
            'unpublished_by_type',
            'to_publish_at',
            'to_unpublish_at',
            'created_at',
            'created_by_id',
            'created_by_type',
            'updated_at',
            'updated_by_id',
            'updated_by_type',
        ];

        foreach ($translatedFields as $field) {
            if (!isset($data[$field])) {
                // Don't set protected fields to null automatically
                if (in_array($field, $protectedFields)) {
                    continue;
                }

                if (in_array($field, $integerFields)) {
                    $data[$field] = null;
                } else {
                    $data[$field] = '';
                }
            }
        }

        return $data;
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
                            ->url(fn() => $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $localization->language->alpha2]))
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
