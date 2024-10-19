<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Moox\Builder\Resources\ItemResource;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->handleTaxonomies();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            Log::info("Checking taxonomy: $taxonomy", ['isset' => isset($data[$taxonomy]), 'method_exists' => method_exists($record, $taxonomy)]);
            if (isset($data[$taxonomy]) && method_exists($record, $taxonomy)) {
                Log::info("Attempting to sync taxonomy: $taxonomy", ['data' => $data[$taxonomy]]);
                try {
                    $record->$taxonomy()->sync($data[$taxonomy]);
                    Log::info("Successfully synced taxonomy: $taxonomy");
                } catch (\Exception $e) {
                    Log::error("Error syncing taxonomy: $taxonomy", ['error' => $e->getMessage()]);
                }
            }
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
