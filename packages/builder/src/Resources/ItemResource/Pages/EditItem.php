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
        $this->record->refresh();
        $this->handleTaxonomies();
    }

    protected function handleTaxonomies(): void
    {
        $record = $this->record;
        $data = $this->data;

        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            if (isset($data[$taxonomy])) {
                Log::info("Syncing taxonomy: $taxonomy", ['data' => $data[$taxonomy]]);
                $record->$taxonomy()->sync($data[$taxonomy]);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (config('builder.taxonomies', []) as $taxonomy => $settings) {
            $taxonomyModel = app($settings['model']);
            $taxonomyTable = $taxonomyModel->getTable();

            $data[$taxonomy] = $this->record->$taxonomy()->pluck("{$taxonomyTable}.id")->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
