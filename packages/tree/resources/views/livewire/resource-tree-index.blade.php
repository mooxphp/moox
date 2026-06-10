<div
    class="fi-sc fi-sc-has-gap fi-grid lg:fi-grid-cols"
    style="--cols-lg: repeat(12, minmax(0, 1fr)); --cols-default: repeat(1, minmax(0, 1fr));"
>
    <div class="fi-grid-col" style="grid-column: span 3 / span 3;">
        <x-filament::section
            :heading="$configuration->treeHeading()"
            :description="$configuration->treeSubheading()"
            icon="heroicon-o-rectangle-stack"
            compact
        >
            <x-slot name="afterHeader">
                <div class="fi-tree-actions">
                    <x-filament::icon-button
                        type="button"
                        icon="heroicon-m-arrows-pointing-out"
                        color="gray"
                        size="sm"
                        label="Alle aufklappen"
                        tooltip="Alle aufklappen"
                        x-on:click="$store.filamentTreeIndex.expandAll()"
                    />

                    <x-filament::icon-button
                        type="button"
                        icon="heroicon-m-arrows-pointing-in"
                        color="gray"
                        size="sm"
                        label="Alle einklappen"
                        tooltip="Alle einklappen"
                        x-on:click="$store.filamentTreeIndex.collapseAll()"
                    />
                </div>
            </x-slot>

            <div
                class="fi-sc fi-sc-has-gap fi-grid"
                style="--cols-default: repeat(1, minmax(0, 1fr));"
            >
                @if ($isToolbarSearchEnabled || $isToolbarLanguageSwitcherEnabled)
                    @include('filament-tree-index::livewire.partials.tree-toolbar', [
                        'isToolbarSearchEnabled' => $isToolbarSearchEnabled,
                        'isToolbarLanguageSwitcherEnabled' => $isToolbarLanguageSwitcherEnabled,
                    ])
                @endif

                <div
                    class="fi-tabs fi-vertical fi-tree-scroll-panel"
                    wire:key="resource-tree-{{ md5(json_encode($treeBranchIdsWithChildren)) }}"
                    x-data
                    x-init="$store.filamentTreeIndex.configure(@js($treeBranchIdsWithChildren), @js($treeAncestorIdsForSelection))"
                >
                    @include('filament-tree-index::livewire.resource-tree', [
                        'items' => $tree,
                        'parentId' => null,
                        'selectedRecordId' => $selectedRecordId,
                        'configuration' => $configuration,
                        'isRoot' => true,
                    ])
                </div>
            </div>
        </x-filament::section>
    </div>

    <div class="fi-grid-col" style="grid-column: span 9 / span 9;">
        @if ($isCreatingInspector && $inspectorCreatePageClass)
            <x-filament::section
                :heading="$configuration->inspectorHeading()"
                icon="heroicon-o-plus-circle"
                compact
            >
                <div
                    class="fi-tree-inspector-scroll"
                    wire:key="tree-index-inspector-create-{{ $creatingParentId ?? 'root' }}-{{ $lang }}"
                >
                    @livewire($inspectorCreatePageClass, [
                        'configurationKey' => $configurationKey,
                        'parentId' => $creatingParentId,
                        'lang' => $lang,
                    ], key('tree-inspector-create-'.($creatingParentId ?? 'root').'-'.$lang))
                </div>
            </x-filament::section>
        @elseif ($selectedRecordId === null)
            <x-filament::section
                :heading="$configuration->inspectorHeading()"
                icon="heroicon-o-pencil-square"
                compact
            >
                <x-filament::empty-state
                    icon="heroicon-o-cursor-arrow-rays"
                    heading="Kein Eintrag ausgewählt"
                    description="Wähle links einen Eintrag oder erstelle einen neuen."
                />
            </x-filament::section>
        @elseif ($inspectorPageClass)
            <x-filament::section
                :heading="$configuration->inspectorHeading()"
                icon="heroicon-o-pencil-square"
                compact
            >
                <div
                    class="fi-tree-inspector-scroll"
                    wire:key="tree-index-inspector-{{ $selectedRecordId }}-{{ $lang }}"
                >
                    @livewire($inspectorPageClass, [
                        'record' => $selectedRecordId,
                        'lang' => $lang,
                    ], key('tree-inspector-'.$selectedRecordId.'-'.$lang))
                </div>
            </x-filament::section>
        @elseif ($selectedRecord === null)
            <x-filament::section
                :heading="$configuration->inspectorHeading()"
                icon="heroicon-o-exclamation-triangle"
                compact
            >
                <x-filament::empty-state
                    icon="heroicon-o-exclamation-triangle"
                    heading="Eintrag nicht gefunden"
                    description="Der ausgewählte Eintrag ist nicht mehr verfügbar oder nicht sichtbar."
                />
            </x-filament::section>
        @else
            <x-filament::section
                :heading="$configuration->inspectorHeading()"
                icon="heroicon-o-pencil-square"
                compact
            >
                <form
                    id="tree-index-inspector-form"
                    wire:submit="saveSelectedRecord"
                    class="fi-sc fi-sc-has-gap fi-tree-form"
                >
                    <x-filament::section compact secondary>
                        @include('filament-tree-index::livewire.tree-index-form', [
                            'configuration' => $configuration,
                            'parentOptions' => $parentOptions,
                        ])
                    </x-filament::section>
                </form>

                <x-slot name="footer">
                    <div class="fi-sc fi-sc-has-gap fi-tree-footer">
                        <div class="fi-tree-footer-actions">
                            <x-filament::button
                                type="submit"
                                form="tree-index-inspector-form"
                                icon="heroicon-m-check"
                            >
                                {{ $configuration->saveLabel() }}
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                wire:click="createChildNode"
                                color="gray"
                                outlined
                                icon="heroicon-m-plus"
                            >
                                {{ $configuration->createChildLabel() }}
                            </x-filament::button>
                        </div>

                        <x-filament::button
                            type="button"
                            wire:click="deleteSelectedRecord"
                            wire:confirm="{{ $configuration->deleteConfirmMessage() }}"
                            color="danger"
                            outlined
                            icon="heroicon-m-trash"
                        >
                            Löschen
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::section>
        @endif
    </div>
</div>


