<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationship\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Contact\Resources\ContactRelationshipResource;
use Moox\Core\Services\RelationService;

class CreateContactRelationship extends CreateRecord
{
    protected static string $resource = ContactRelationshipResource::class;

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
