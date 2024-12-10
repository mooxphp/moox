<?php

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait SingleSoftDeleteInViewPage
{
    public function mount(Model|int|string $record): void
    {
        $model = static::getModel();

        if ($record instanceof Model) {
            $recordId = $record->getKey();
        } else {
            $recordId = $record;
        }

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        $foundRecord = $query->find($recordId);

        if (! $foundRecord) {
            throw new \Exception("Record with ID {$recordId} not found.");
        }

        parent::mount($recordId);

        $this->record = $foundRecord;

        $this->fillForm();
    }

    // TODO: this clashes with other traits, if we need, it must be abstracted
    /*    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->trashed()) {
            // You can add a banner, or adjust the layout to show it's deleted
        }

        return $data;
    }
    */

    public function getFormActions(): array
    {
        return [];
    }
}
