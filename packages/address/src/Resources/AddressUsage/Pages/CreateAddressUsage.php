<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsage\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Address\Resources\AddressUsageResource;
use Moox\Core\Services\RelationService;

class CreateAddressUsage extends CreateRecord
{
    protected static string $resource = AddressUsageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $model = static::getModel();

        if (! method_exists($model, 'getResourceName')) {
            return $data;
        }

        return app(RelationService::class)
            ->forResource($model::getResourceName())
            ->applyBelongsToCreatePrefill($data);
    }
}
