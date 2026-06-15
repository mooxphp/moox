<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Services\FieldGroupPersistence;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;

    protected function handleRecordCreation(array $data): FieldGroup
    {
        $group = new FieldGroup;
        app(FieldGroupPersistence::class)->sync($group, $data);

        return $group->fresh(['fields.options']);
    }
}
