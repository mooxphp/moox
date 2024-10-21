<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Resources\ItemResource;
use Moox\Builder\Traits\HandlesDynamicTaxonomies;

class EditItem extends EditRecord
{
    use HandlesDynamicTaxonomies;

    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        $this->handleTaxonomies();
        $this->refreshFormData($this->getTaxonomyAttributes());
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->mutateFormDataBeforeFillWithTaxonomies($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function getTaxonomyAttributes(): array
    {
        return array_keys(config('builder.taxonomies', []));
    }
}
