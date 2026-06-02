@php
    $hasTreeChildren = $item['children'] !== [];
    $isSelected = $selectedRecordId === $item['id'];
@endphp

<li
    wire:key="resource-tree-item-{{ $item['id'] }}"
    @if ($reorderable) wire:sort:item="{{ $item['id'] }}" @endif
    class="fi-tree-item"
>
    @if ($reorderable)
        <x-filament::icon-button
            type="button"
            icon="heroicon-m-bars-3"
            color="gray"
            size="sm"
            label="Eintrag verschieben"
            tooltip="Verschieben"
            wire:sort:handle
        />
    @endif

    <div class="fi-tree-item-content" @if ($reorderable) wire:sort:ignore @endif>
        <button
            type="button"
            wire:click.stop="selectRecord({{ $item['id'] }})"
            @class([
                'fi-tabs-item fi-tabs-item-vertical fi-tree-item-button',
                'fi-active' => $isSelected,
            ])
        >
            <x-filament::icon
                :icon="$hasTreeChildren ? 'heroicon-m-folder' : 'heroicon-m-document-text'"
                class="fi-tabs-item-icon"
            />

            <span class="fi-tabs-item-label fi-tree-item-label">
                {{ $item['label'] }}
            </span>
        </button>
    </div>

    @if ($hasTreeChildren)
        @if ($reorderable)
            <x-filament::icon-button
                type="button"
                icon="heroicon-m-chevron-right"
                color="gray"
                size="sm"
                :label="'Untereinträge von '.$item['label']"
                tooltip="Untereinträge ein-/ausklappen"
                wire:sort:ignore
                class="fi-tree-chevron"
                x-on:click.stop="$store.filamentTreeIndex.toggle({{ $item['id'] }})"
                x-bind:aria-expanded="$store.filamentTreeIndex.open[{{ $item['id'] }}] ? 'true' : 'false'"
                x-bind:class="$store.filamentTreeIndex.open[{{ $item['id'] }}] ? 'fi-tree-chevron-open' : ''"
            />
        @else
            <x-filament::icon-button
                type="button"
                icon="heroicon-m-chevron-right"
                color="gray"
                size="sm"
                :label="'Untereinträge von '.$item['label']"
                tooltip="Untereinträge ein-/ausklappen"
                class="fi-tree-chevron"
                x-on:click.stop="$store.filamentTreeIndex.toggle({{ $item['id'] }})"
                x-bind:aria-expanded="$store.filamentTreeIndex.open[{{ $item['id'] }}] ? 'true' : 'false'"
                x-bind:class="$store.filamentTreeIndex.open[{{ $item['id'] }}] ? 'fi-tree-chevron-open' : ''"
            />
        @endif
    @endif
</li>

@if ($hasTreeChildren)
    <li
        wire:key="resource-tree-branch-{{ $item['id'] }}"
        class="fi-tree-branch"
        x-show="$store.filamentTreeIndex.open[{{ $item['id'] }}]"
        x-cloak
    >
        @include('filament-tree-index::livewire.resource-tree', [
            'items' => $item['children'],
            'parentId' => $item['id'],
            'selectedRecordId' => $selectedRecordId,
            'configuration' => $configuration,
            'isRoot' => false,
        ])
    </li>
@endif
