<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Support\Resources\ScopedResourceContext;

abstract class BaseCreateItem extends CreateRecord
{
    use CanResolveResourceClass;

    public function getFormActions(): array
    {
        return [

        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        /** @var Model $record */
        $record = new $model;

        $record->fill($data);
        // Ensure the record gets the correct scope in scoped Resource contexts.
        ScopedResourceContext::applyDefaults($record, static::getResource());
        $record->save();

        return $record;
    }
}
