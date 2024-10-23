<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\HandlesDynamicTaxonomies;

class ViewItem extends ViewRecord
{
    use HandlesDynamicTaxonomies;

    protected static string $resource = ItemResource::class;

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();
    }

    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    private function isRecordTrashed(): bool
    {
        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function resolveRecord($key): Model
    {
        $model = static::getResource()::getModel();

        $record = $model::findOrFail($key);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
