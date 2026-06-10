<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Actions\Tree\AssignTreeNodePositionAction;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;

/**
 * @mixin CreateRecord
 */
trait RendersAsTreeIndexCreateInspector
{
    use InteractsWithTreeIndexInspectorLocale;
    use RendersAsTreeIndexEmbeddedPage;

    protected bool $embeddedInTreeIndexCreateInspector = true;

    public string $configurationKey = '';

    public ?int $parentId = null;

    public function mount(): void
    {
        $this->syncTreeInspectorLocaleToRequest();

        parent::mount();

        $this->applyTreeCreateParentToForm();
    }

    protected function isEmbeddedInTreeIndex(): bool
    {
        return $this->embeddedInTreeIndexCreateInspector;
    }

    protected function getRedirectUrl(): string
    {
        return '';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::resources/pages/create-record.notifications.created.title'))
            ->success();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        if ($this->configurationKey === '') {
            return $data;
        }

        $configuration = TreeIndexConfigurationRegistry::resolve($this->configurationKey);
        $parentColumn = $configuration->getParentColumn();
        $parentId = $data[$parentColumn] ?? $this->parentId;

        if ($this->parentId !== null && ! array_key_exists($parentColumn, $data)) {
            $data[$parentColumn] = $this->parentId;
        }

        return app(AssignTreeNodePositionAction::class, ['configuration' => $configuration])
            ->applyAdjacencySortToFormData($data, $parentId);
    }

    protected function afterCreate(): void
    {
        if (method_exists(parent::class, 'afterCreate')) {
            parent::afterCreate();
        }

        if ($this->configurationKey !== '') {
            $configuration = TreeIndexConfigurationRegistry::resolve($this->configurationKey);

            /** @var Model $record */
            $record = $this->getRecord();

            app(AssignTreeNodePositionAction::class, ['configuration' => $configuration])
                ->positionNestedSetAfterCreate($record, $this->parentId);
        }

        /** @var Model $record */
        $record = $this->getRecord();

        $this->dispatch('tree-index-record-created', recordId: (int) $record->getKey());
    }

    protected function applyTreeCreateParentToForm(): void
    {
        if ($this->configurationKey === '' || $this->parentId === null) {
            return;
        }

        $parentColumn = TreeIndexConfigurationRegistry::resolve($this->configurationKey)->getParentColumn();

        $this->form->fill([
            $parentColumn => $this->parentId,
        ]);
    }
}
