<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Override;

class CreateDraft extends BaseCreateDraft
{
    public ?string $lang = null;

    #[Override]
    public function mount(): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        $record = new $model;

        // Set the default locale before saving
        $record->setDefaultLocale($this->lang);

        // Get translatable and non-translatable attributes
        $translatableAttributes = $record->translatedAttributes;
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        // Fill and save the main record with non-translatable data
        $record->fill($nonTranslatableData);
        $record->save();

        // Create the translation
        $translation = $record->translations()->firstOrNew([
            'locale' => $this->lang,
        ]);

        // Set translation data
        foreach ($translatableAttributes as $attr) {
            if (isset($translationData[$attr])) {
                $translation->setAttribute($attr, $translationData[$attr]);
            }
        }

        // Set author ID for the translation
        $translation->author_id = auth()->id();

        // Save the translation
        $record->translations()->save($translation);

        return $record;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }
}
