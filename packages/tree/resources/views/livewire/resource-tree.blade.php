@php
    $reorderable = $configuration->isReorderable();
@endphp

<ul
    @if ($reorderable)
        wire:sort="moveTreeNode"
        wire:sort:group="resource-tree-nodes"
        wire:sort:group-id="{{ $parentId ?? 'root' }}"
    @endif
    @class([
        'fi-sc fi-sc-has-gap fi-tree-list',
        'fi-tree-list-root' => $isRoot ?? false,
        'fi-tree-list-child' => ! ($isRoot ?? false),
    ])
    @if ($isRoot ?? false)
        role="tree"
        aria-label="{{ $configuration->treeHeading() }}"
    @endif
>
    @forelse ($items as $item)
        @include('filament-tree-index::livewire.partials.resource-tree-item', [
            'item' => $item,
            'reorderable' => $reorderable,
            'selectedRecordId' => $selectedRecordId,
            'configuration' => $configuration,
        ])
    @empty
        @if ($isRoot ?? false)
            <li>
                <x-filament::empty-state
                    icon="heroicon-o-rectangle-stack"
                    heading="Noch keine Einträge vorhanden"
                    description="Lege den ersten Eintrag mit der Schaltfläche unten an."
                />
            </li>
        @endif
    @endforelse
</ul>
