<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\Communication\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Communication\Resources\CommunicationResource;
use Moox\Core\Services\RelationService;

class CreateCommunication extends CreateRecord
{
    protected static string $resource = CommunicationResource::class;

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
