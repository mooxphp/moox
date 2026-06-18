<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Standalone tree-inspector edit page (direct route). Tree index uses inline {@see InteractsWithTreeResourceInspectorForm}.
 *
 * @mixin EditRecord
 */
trait RendersAsTreeIndexInspector
{
    use InteractsWithTreeIndexInspectorLocale;
    use RendersAsTreeIndexEmbeddedPage;

    protected bool $embeddedInTreeIndexInspector = true;

    public function mount($record): void
    {
        $this->syncTreeInspectorLocaleToRequest();

        parent::mount($record);
    }

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
}
