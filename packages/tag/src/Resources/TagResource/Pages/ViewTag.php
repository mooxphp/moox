<?php

declare(strict_types=1);

namespace Moox\Tag\Resources\TagResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Tag\Resources\TagResource;
use Override;

class ViewTag extends ViewRecord
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
    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make()->hidden(fn () => $this->isRecordTrashed()),
            // RestoreAction::make()->visible(fn () => $this->isRecordTrashed()),
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    private function isRecordTrashed(): bool
    {
        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }

}
