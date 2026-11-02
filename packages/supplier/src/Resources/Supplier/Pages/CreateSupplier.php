<?php

declare(strict_types=1);

namespace Moox\Supplier\Resources\Supplier\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Services\RelationService;
use Moox\Supplier\Resources\SupplierResource;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

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
