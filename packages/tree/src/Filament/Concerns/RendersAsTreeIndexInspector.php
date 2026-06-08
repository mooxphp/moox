<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * @mixin EditRecord
 */
trait RendersAsTreeIndexInspector
{
    use RendersAsTreeIndexEmbeddedPage;

    protected bool $embeddedInTreeIndexInspector = true;

    protected function isEmbeddedInTreeIndex(): bool
    {
        return $this->embeddedInTreeIndexInspector;
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->success();
    }

    protected function afterSave(): void
    {
        $this->dispatch('tree-index-record-saved');
    }
}
