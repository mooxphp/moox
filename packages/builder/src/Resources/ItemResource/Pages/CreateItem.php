<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Moox\Builder\Resources\ItemResource;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function afterCreate(): void
    {
        $this->handleTaxonomies();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            if (isset($data[$taxonomy]) && method_exists($record, $taxonomy)) {
                Log::info("Syncing taxonomy: $taxonomy", ['data' => $data[$taxonomy]]);
                $record->$taxonomy()->sync($data[$taxonomy]);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
