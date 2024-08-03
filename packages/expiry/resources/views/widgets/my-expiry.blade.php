<div class="grid flex-1 auto-cols-fr gap-y-8">
    <div class="flex flex-col gap-y-6">
        <nav class="fi-tabs flex max-w-full gap-x-1 overflow-x-auto mx-auto rounded-xl bg-white p-2 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" role="tablist">
            @foreach ($tabs as $tab)
                <button wire:click="switchTab('{{ $tab->key }}')"
                        class="fi-tabs-item group flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium outline-none transition duration-75 {{ $activeTab === $tab->key ? 'fi-active fi-tabs-item-active bg-gray-50 dark:bg-white/5' : 'hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5' }}"
                        aria-selected="{{ $activeTab === $tab->key ? 'true' : 'false' }}"
                        role="tab">
                    @svg($tab->icon, 'fi-tabs-item-icon h-5 w-5 shrink-0 transition duration-75 ' . ($activeTab === $tab->key ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500'))
                    <span class="fi-tabs-item-label transition duration-75 {{ $activeTab === $tab->key ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 group-hover:text-gray-700 group-focus-visible:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-200 dark:group-focus-visible:text-gray-200' }}">
                        {{ $tab->label }}
                    </span>
                    <span class="fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-1.5 min-w-[theme(spacing.5)] py-0.5 tracking-tight fi-color-custom {{ $activeTab === $tab->key ? 'bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30' : 'bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30' }}">
                        <span class="grid">
                            <span class="truncate">
                                {{ $tab->badge }}
                            </span>
                        </span>
                    </span>
                </button>
            @endforeach
        </nav>

        <div>
            {{ $this->table }}
        </div>
    </div>
</div>
