@livewire(
    config('filament-tree-index.livewire.alias', 'filament-tree-index'),
    [
        'configurationKey' => $configurationKey,
        'lang' => $this->lang,
        'search' => $this->tableSearch ?? '',
    ],
    key('filament-tree-index-'.$configurationKey.'-'.md5(json_encode([$this->tableFilters ?? [], $this->tableSearch ?? '', $this->activeTab ?? '', $this->lang ?? ''])))
)
