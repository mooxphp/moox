<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft\Pages;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Override;

class CreateDraft extends BaseCreateDraft
{
    public ?string $selectedLang = null;

    #[Override]
    public function mount(): void
    {
        $this->selectedLang = request()->query('lang');
        parent::mount();
    }

    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();

        $record = new $model;

        $translatableAttributes = ['title', 'slug', 'description', 'content'];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        $record->fill($nonTranslatableData);
        $record->save();

        if ($this->selectedLang) {
            $record->translateOrNew($this->selectedLang)->fill($translationData);
        } else {
            $record->translateOrNew(app()->getLocale())->fill($translationData);
        }

        $record->save();

        return $record;
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->selectedLang]);
    }
}
