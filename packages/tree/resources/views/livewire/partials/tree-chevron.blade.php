@props([
    'itemLabel',
    'itemId',
    'reorderable' => false,
])

<div @if ($reorderable) wire:sort:ignore @endif class="fi-tree-chevron-wrap">
    <x-filament::icon-button
        type="button"
        icon="heroicon-m-chevron-right"
        color="gray"
        size="sm"
        :label="'Untereinträge von '.$itemLabel"
        tooltip="Untereinträge ein-/ausklappen"
        class="fi-tree-chevron"
        x-on:click.stop="$store.filamentTreeIndex.toggle({{ $itemId }})"
        x-bind:aria-expanded="$store.filamentTreeIndex.open[{{ $itemId }}] ? 'true' : 'false'"
        x-bind:class="{ 'fi-tree-chevron-open': $store.filamentTreeIndex.open[{{ $itemId }}] }"
    />
</div>
