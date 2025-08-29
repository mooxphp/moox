<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

abstract class BaseCreateRecord extends CreateRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    protected function resolveRecord($key): Model
    {
        $model = static::getModel();

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->find($key) ?? $model::make();
    }

    public function getFormActions(): array
    {
        return [];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $this->handleTaxonomiesBeforeFill($data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        /** @var Model $record */
        $record = new $model;

        $record->fill($data);
        $record->save();

        // Save taxonomy data if available
        $this->saveTaxonomyDataForRecord($record, $data);

        return $record;
    }
}
