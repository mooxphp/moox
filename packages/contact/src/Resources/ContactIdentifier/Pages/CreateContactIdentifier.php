<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifier\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactIdentifierResource;
use Moox\Core\Services\RelationService;

class CreateContactIdentifier extends CreateRecord
{
    protected static string $resource = ContactIdentifierResource::class;

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
