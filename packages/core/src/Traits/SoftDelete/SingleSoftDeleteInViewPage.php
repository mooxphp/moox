<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait SingleSoftDeleteInViewPage
{
    public function mount(Model|int|string $record): void
    {
        $model = static::getModel();

        $recordId = $record instanceof Model ? $record->getKey() : $record;

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        $foundRecord = $query->find($recordId);

        if (! $foundRecord) {
            throw new Exception(sprintf('Record with ID %s not found.', $recordId));
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
