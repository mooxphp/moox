<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Tag\Resources\TagResource;
use Override;
use Illuminate\Database\Eloquent\Model;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    public ?string $selectedLang = null;

    #[Override]
    public function mount($record): void
    {
        $this->selectedLang = request()->query('lang');
        parent::mount($record);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->selectedLang && $this->record->hasTranslation($this->selectedLang)) {
            $translation = $this->record->translate($this->selectedLang);
            return array_merge($data, [
                'title' => $translation->title,
                'slug' => $translation->slug,
                'content' => $translation->content,
            ]);
        }
        
        return $data;
    }

    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Get translatable attributes
        $translatableAttributes = ['title', 'slug', 'content'];
        $translationData = array_intersect_key($data, array_flip($translatableAttributes));
        $nonTranslatableData = array_diff_key($data, array_flip($translatableAttributes));
        
        // Update non-translatable data
        $record->fill($nonTranslatableData);
        
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

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
