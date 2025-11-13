<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

abstract class BaseEditRecord extends EditRecord
{
    use CanResolveResourceClass, HasPagesTaxonomy;

    protected function getFormActions(): array
    {
        return [];
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $this->handleTaxonomiesBeforeFill($data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update(attributes: $data);

        $this->saveTaxonomyDataForRecord($record, $data);

        return $record;
    }
}
