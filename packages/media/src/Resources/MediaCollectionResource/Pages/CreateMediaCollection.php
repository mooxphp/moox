<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource;

class CreateMediaCollection extends CreateRecord
{
    protected static string $resource = MediaCollectionResource::class;

    public ?string $lang = null;

    public function mount(): void
    {
        $this->lang = request()->query('lang', app()->getLocale());
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('language_selector')
                ->view('localization::lang-selector')
                ->extraAttributes(['style' => 'margin-left: -8px;']),
        ];
    }

    protected function handleRecordCreation(array $data): MediaCollection
    {
        if (isset($data['extend_existing_collection']) && $data['extend_existing_collection']) {
            $existingCollection = MediaCollection::query()->find($data['extend_existing_collection']);
            if ($existingCollection) {
                $translation = $existingCollection->translateOrNew($this->lang);
                $translation->setAttribute('name', $data['name']);
                $translation->setAttribute('description', $data['description'] ?? '');
                $existingCollection->save();

                $this->record = $existingCollection;

                return $existingCollection;
            }
        }

        unset($data['extend_existing_collection']);

        $collection = new MediaCollection;
        $translation = $collection->translateOrNew($this->lang);
        $translation->setAttribute('name', $data['name']);
        $translation->setAttribute('description', $data['description'] ?? '');
        $collection->save();

        return $collection;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['lang' => $this->lang]);
    }
}
