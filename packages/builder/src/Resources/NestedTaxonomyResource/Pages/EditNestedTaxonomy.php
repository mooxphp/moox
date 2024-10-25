<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\NestedTaxonomyResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Resources\NestedTaxonomyResource;
use Moox\Core\Traits\TaxonomyInPages;

class EditNestedTaxonomy extends EditRecord
{
    use TaxonomyInPages;

    protected static string $resource = NestedTaxonomyResource::class;

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function resolveRecord($key): Model
    {
        $model = static::getResource()::getModel();

        $record = $model::findOrFail($key);

        return $record;
    }
}
