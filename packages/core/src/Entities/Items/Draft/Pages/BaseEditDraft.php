<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;
use Moox\Localization\Models\Localization;

/**
 * @phpstan-type TranslatableModel = Model&TranslatableContract
 *
 * @phpstan-property-read array<string> $translatedAttributes
 */
abstract class BaseEditDraft extends EditRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    public ?string $lang = null;

    protected function getFormActions(): array
    {
        return [];
    }

    public function mount($record): void
    {
        $defaultLocalization = Localization::where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();

        $this->lang = request()->query('lang', $defaultLang);
        parent::mount($record);

        if ($this->record && $this->record instanceof Model && method_exists($this->record, 'translations')) {
            $isAdminContext = request()->is('admin/*') || request()->is('filament/*');

            if ($isAdminContext) {
                $localization = Localization::where('locale_variant', $this->lang)
                    ->where('is_active_admin', true)->first();

                if (! $localization) {
                    $defaultLocalization = Localization::where('is_default', true)->first();
                    if ($defaultLocalization) {
                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $defaultLocalization->locale_variant]));
                    } else {
                        $this->redirect($this->getResource()::getUrl('index'));
                    }
                }
            }

            $translation = $this->record->translations()->withTrashed()->where('locale', $this->lang)->first();

            if ($this->record->trashed() && ! $translation) {
                $defaultLocalization = Localization::where('is_default', true)->first();
                if ($defaultLocalization) {
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $defaultLocalization->locale_variant]));
                } else {
                    $this->redirect($this->getResource()::getUrl('index'));
                }
            }

            if ($translation && $translation->trashed()) {
                $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $this->lang]));
            }
        }
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

        // Handle taxonomies
        $this->handleTaxonomiesBeforeFill($values);

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

        $translationData = $data['translations'][$this->lang] ?? [];

        unset($data['translations']);

        $record->update($data);

        if (! empty($translationData)) {
            $relation = $record->translations();
            $translationModel = $relation->getRelated();
            $foreignKey = $relation->getForeignKeyName();

            $translation = $record->translations()
                ->where('locale', $this->lang)
                ->first();

            if (! $translation) {
                $translation = $record->translations()->make([
                    $relation->getForeignKeyName() => $record->getKey(),
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
            if (! isset($data[$field])) {
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

        $this->handleTaxonomiesBeforeSave($data);

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

    public function getTitle(): string
    {
        $entity = $this->getResource()::getModelLabel();

        $translation = $this->record->translations()->where('locale', $this->lang)->first();

        if ($translation && $translation->title) {
            return $entity.' - '.$translation->title;
        }

        return $entity.' - '.__('core::core.translation_create');
    }

    public function getHeading(): string
    {
        $entity = $this->getResource()::getModelLabel();

        $translation = $this->record->translations()->where('locale', $this->lang)->first();

        if ($translation && $translation->title) {
            return $entity.' - '.$translation->title;
        }

        $title = $entity.' - '.__('core::core.translation_create');

        $defaultLocalization = Localization::where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? app()->getLocale();
        $fallbackTranslation = $this->record->translations()->where('locale', $defaultLang)->first();

        if ($fallbackTranslation && $fallbackTranslation->title) {
            $title .= ' ('.$fallbackTranslation->title.' - '.$defaultLang.')';
        } else {
            $anyTranslation = $this->record->translations()->whereNotNull('title')->first();
            if ($anyTranslation && $anyTranslation->title) {
                $title .= ' ('.$anyTranslation->title.' - '.$anyTranslation->locale.')';
            }
        }

        return $title;
    }
}
