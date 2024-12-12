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

    public function getFormActions(): array
    {
        return [];
    }
}
