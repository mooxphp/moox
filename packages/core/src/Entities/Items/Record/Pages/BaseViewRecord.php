<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseViewRecord extends ViewRecord
{
    use CanResolveResourceClass;

    public function getFormActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $title = parent::getTitle();
        if ($this->isRecordTrashed()) {
            $title = $title.' - '.__('core::core.deleted');
        }

        return $title;
    }

    protected function isRecordTrashed(): bool
    {
        if (! $this->record) {
            return false;
        }

        return $this->record instanceof Model && method_exists($this->record, 'trashed') && $this->record->trashed();
    }
}
