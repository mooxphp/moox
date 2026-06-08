@props([
    'itemLabel',
    'itemId',
    'reorderable' => false,
])

<x-filament::icon-button
    type="button"
    icon="heroicon-m-chevron-right"
    color="gray"
    size="sm"
    :label="'Untereinträge von '.$itemLabel"
    tooltip="Untereinträge ein-/ausklappen"
    @if ($reorderable) wire:sort:ignore @endif
    class="fi-tree-chevron"
    x-on:click.stop="$store.filamentTreeIndex.toggle({{ $itemId }})"
    x-bind:aria-expanded="$store.filamentTreeIndex.open[{{ $itemId }}] ? 'true' : 'false'"
    x-bind:class="$store.filamentTreeIndex.open[{{ $itemId }}] ? 'fi-tree-chevron-open' : ''"
/>
