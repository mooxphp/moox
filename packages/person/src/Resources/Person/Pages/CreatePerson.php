<?php

declare(strict_types=1);

namespace Moox\Person\Resources\Person\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Services\RelationService;
use Moox\Person\Resources\PersonResource;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;

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
