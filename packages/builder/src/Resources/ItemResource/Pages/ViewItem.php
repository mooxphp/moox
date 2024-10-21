<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Services\TaxonomyService;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected function getTaxonomyService(): TaxonomyService
    {
        return app(TaxonomyService::class);
    }

    public function mount($record = null): void
    {
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make()->hidden(fn () => $this->isRecordTrashed()),
            // RestoreAction::make()->visible(fn () => $this->isRecordTrashed()),
        ];
    }

    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->getTaxonomyService()->getTaxonomies() as $taxonomy => $settings) {
            $taxonomyModel = app($settings['model']);
            $taxonomyTable = $taxonomyModel->getTable();

            $data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
        }

        return $data;
    }

    private function isRecordTrashed(): bool
    {
        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }
}
