<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

/**
 * @mixin EditRecord
 */
trait RendersAsTreeIndexInspector
{
    protected bool $embeddedInTreeIndexInspector = true;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    /**
     * @param  mixed  $url
     */
    public function redirect($url, $navigate = false): void
    {
        if ($this->embeddedInTreeIndexInspector) {
            return;
        }

        parent::redirect($url, $navigate);
    }

    public function getView(): string
    {
        return 'filament-tree-index::filament.pages.tree-index-inspector';
    }

    public function render(): View
    {
        return view($this->getView(), $this->getViewData());
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * @return array<Action>
     */
    public function getHeaderActions(): array
    {
        return [];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
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
