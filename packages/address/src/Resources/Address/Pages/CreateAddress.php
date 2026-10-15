<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Address\Resources\AddressResource;
use Moox\Core\Services\RelationService;

class CreateAddress extends CreateRecord
{
    protected static string $resource = AddressResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $model = static::getModel();

        if (method_exists($model, 'getResourceName')) {
            $data = app(RelationService::class)
                ->forResource($model::getResourceName())
                ->applyBelongsToCreatePrefill($data);
        }

        foreach (['owner_id', 'owner_type'] as $field) {
            if (filled($data[$field] ?? null)) {
                continue;
            }

            $prefill = request()->query($field);

            if (filled($prefill)) {
                $data[$field] = $prefill;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['owner_id', 'owner_type'] as $field) {
            if (filled($data[$field] ?? null)) {
                continue;
            }

            $prefill = request()->query($field);

            if (filled($prefill)) {
                $data[$field] = $prefill;
            }
        }

        return $data;
    }
}
