{{-- @deprecated Tree pages use ListRecords content() + EmbeddedTable; kept for reference. --}}
<x-filament-panels::page>
    @livewire(
        config('filament-tree-index.livewire.alias', 'filament-tree-index'),
        ['configurationKey' => $treeIndexConfigurationKey],
        key('filament-tree-index-'.$treeIndexConfigurationKey)
    )
</x-filament-panels::page>
