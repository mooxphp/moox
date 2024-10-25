<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\TaxonomyInPages;

class EditItem extends EditRecord
{
    use TaxonomyInPages;

    protected static string $resource = ItemResource::class;

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
}
