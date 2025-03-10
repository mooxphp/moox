<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Tag\Resources\TagResource;
use Override;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

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
        // Create new record instance
        $record = new $model;

        // Get translatable attributes
        $translatableAttributes = ['title', 'slug', 'content'];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));

        // Fill non-translatable data
        $record->fill($nonTranslatableData);
        $record->save();
        // Handle translations
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

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
