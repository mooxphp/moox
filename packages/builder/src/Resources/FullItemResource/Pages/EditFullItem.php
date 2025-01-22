<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FullItemResource\Pages;

use Override;
use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Resources\FullItemResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInPages;

class EditFullItem extends EditRecord
{
    use TaxonomyInPages;

    protected static string $resource = FullItemResource::class;

    #[Override]
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

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
