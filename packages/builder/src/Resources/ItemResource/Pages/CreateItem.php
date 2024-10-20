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
        $this->record->refresh();
        $this->handleTaxonomies();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        if (! ($record instanceof \Moox\Builder\Models\Item)) {
            return;
        }

        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                Log::info("Syncing taxonomy: $taxonomy", ['data' => $data[$taxonomy]]);
                if (method_exists($record, $taxonomy)) {
                    $record->$taxonomy()->sync($data[$taxonomy]);
                }
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
}
