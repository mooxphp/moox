<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;

/**
 * @mixin CreateRecord
 */
trait RendersAsTreeIndexCreateInspector
{
    protected bool $embeddedInTreeIndexCreateInspector = true;

    public string $configurationKey = '';

    public ?int $parentId = null;

    public function mount(): void
    {
        parent::mount();

        $this->applyTreeCreateParentToForm();
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return false;
    }

    /**
     * @param  mixed  $url
     */
    public function redirect($url, $navigate = false): void
    {
        if ($this->embeddedInTreeIndexCreateInspector) {
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

        $configuration = TreeIndexConfigurationRegistry::get($this->configurationKey);
        $parentColumn = $configuration->getParentColumn();
        $parentId = $data[$parentColumn] ?? $this->parentId;

        if ($this->parentId !== null && ! array_key_exists($parentColumn, $data)) {
            $data[$parentColumn] = $this->parentId;
        }

        if (! $configuration->usesNestedSet()) {
            $sortColumn = $configuration->getSortColumn();
            $maxSort = $configuration->siblingsQuery($parentId)->max($sortColumn);
            $data[$sortColumn] = ((int) $maxSort) + 10;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (method_exists(parent::class, 'afterCreate')) {
            parent::afterCreate();
        }

        if ($this->configurationKey !== '') {
            $this->applyNestedSetPosition(TreeIndexConfigurationRegistry::get($this->configurationKey));
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

        $parentColumn = TreeIndexConfigurationRegistry::get($this->configurationKey)->getParentColumn();

        $this->form->fill([
            $parentColumn => $this->parentId,
        ]);
    }

    protected function applyNestedSetPosition(TreeIndexConfiguration $configuration): void
    {
        if (! $configuration->usesNestedSet()) {
            return;
        }

        $modelClass = $configuration->modelClass();

        if (! in_array(NodeTrait::class, class_uses_recursive($modelClass), true)) {
            return;
        }

        /** @var Model $record */
        $record = $this->getRecord();

        if (! method_exists($record, 'appendToNode') || ! method_exists($record, 'saveAsRoot')) {
            return;
        }

        $parentId = $this->parentId;

        if ($parentId === null) {
            $parentId = $record->getAttribute($configuration->getParentColumn());
        }

        if ($parentId !== null) {
            $parent = $configuration->newQuery()->find((int) $parentId);

            if ($parent) {
                $record->appendToNode($parent)->save();
            }

            return;
        }

        $record->saveAsRoot();
    }
}
