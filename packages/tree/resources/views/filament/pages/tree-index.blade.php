<x-filament-panels::page>
    @livewire(
        config('filament-tree-index.livewire.alias', 'filament-tree-index'),
        ['configurationKey' => $treeIndexConfigurationKey],
        key('filament-tree-index-'.$treeIndexConfigurationKey)
    )
</x-filament-panels::page>
