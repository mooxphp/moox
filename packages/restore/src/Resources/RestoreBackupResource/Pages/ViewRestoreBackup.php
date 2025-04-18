<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreBackupResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Restore\Resources\RestoreBackupResource;

class ViewRestoreBackup extends ViewRecord
{
    protected static string $resource = RestoreBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make()->hidden(fn () => $this->isRecordTrashed()),
            // RestoreAction::make()->visible(fn () => $this->isRecordTrashed()),
        ];
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
}
