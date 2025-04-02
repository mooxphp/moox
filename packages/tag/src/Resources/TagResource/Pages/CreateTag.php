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

        // Separate translatable and non-translatable data
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        /** @var \Moox\Tag\Models\Tag $model */
        $tag = $model::createWithTranslations($data, $translations);

        return $tag;
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
