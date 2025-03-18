<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Tag\Resources\TagResource;
use Override;

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

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, [
            'translations' => $this->record->getTranslationsArray(),
        ]);
    }

    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Separate translatable and non-translatable data
        $translations = $data['translations'] ?? [];
        unset($data['translations']);

        return $record->updateWithTranslations($data, $translations);
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
