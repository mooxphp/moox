<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleTaxonomyResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Resources\SimpleTaxonomyResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInPages;
use Override;

class ViewSimpleTaxonomy extends ViewRecord
{
    use TaxonomyInPages;

    protected static string $resource = SimpleTaxonomyResource::class;

    #[Override]
    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();
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

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
