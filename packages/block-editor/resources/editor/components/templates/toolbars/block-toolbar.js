/**
 * Block Toolbar Template
 * Toolbar zum Hinzufügen neuer Blöcke mit Theme-Vorlagen
 *
 * @param  {boolean}  allowThemeTab  Wenn false: kein Tab „Theme Vorlagen“, nur Block-Typen (für Filament `->templates(false)`).
 */
export function getBlockToolbarTemplate(allowThemeTab = true) {
    const blockCategories = [
        {
            key: 'layout',
            label: 'Layout',
            buttonClasses: 'px-3 py-2 bg-blue-50 text-blue-800 rounded hover:bg-blue-100 text-sm transition-colors'
        },
        {
            key: 'text',
            label: 'Text',
            buttonClasses: 'px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm transition-colors'
        },
        {
            key: 'media',
            label: 'Media',
            buttonClasses: 'px-3 py-2 bg-emerald-50 text-emerald-800 rounded hover:bg-emerald-100 text-sm transition-colors'
        },
        {
            key: 'interactive',
            label: 'Data/Interaktiv',
            buttonClasses: 'px-3 py-2 bg-amber-50 text-amber-800 rounded hover:bg-amber-100 text-sm transition-colors'
        }
    ];
    const categorySections = blockCategories
        .map((category) => `
                            <div x-show="Object.entries(childBlockTypes).some(([type, cfg]) => (cfg?.category || 'text') === '${category.key}' && matchesToolbarSearch(type, cfg))">
                                <div class="mb-2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">${category.label}</span>
                                </div>
                                <div class="flex gap-2 flex-wrap">
                                    <template x-for="[blockType, config] in Object.entries(childBlockTypes).filter(([type, cfg]) => (cfg?.category || 'text') === '${category.key}' && matchesToolbarSearch(type, cfg))" :key="'${category.key}-' + blockType">
                                        <button type="button" @click="addBlock(blockType)"
                                            class="${category.buttonClasses}">
                                            <span x-text="config.label || blockType"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>`)
        .join('');
    const blocksPanel = `
                        <!-- Block-Typen - gruppiert nach Kategorie -->
                        <div class="space-y-5">
                            ${categorySections}
                        </div>`;

    const tabbedBody = allowThemeTab
        ? `
                    <!-- Tabs -->
                    <div class="flex gap-2 mb-4 border-b border-gray-200">
                        <button type="button" @click="showToolbarTab = 'blocks'"
                            :class="showToolbarTab === 'blocks' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 font-semibold transition-colors">
                            Block hinzufügen
                        </button>
                        <button type="button" @click="showToolbarTab = 'themes'"
                            :class="showToolbarTab === 'themes' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 font-semibold transition-colors">
                            Theme Vorlagen
                        </button>
                    </div>

                    <!-- Tab: Block hinzufügen -->
                    <div x-show="showToolbarTab === 'blocks'">
                        ${blocksPanel}
                        <div x-show="Object.entries(childBlockTypes).filter(([type, cfg]) => matchesToolbarSearch(type, cfg)).length === 0"
                            class="text-sm text-gray-500 py-3">
                            Keine Blöcke gefunden.
                        </div>
                    </div>

                    <!-- Tab: Theme Vorlagen -->
                    <div x-show="showToolbarTab === 'themes'">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-600">🎨 Theme-Vorlagen:</span>
                        </div>
                        <div class="space-y-1 max-h-64 overflow-y-auto">
                            <template x-for="theme in themes" :key="theme.name">
                                <button type="button" @click.stop="loadThemeFromToolbar(theme.name); closeBlockToolbar()"
                                    class="w-full px-3 py-2 bg-white rounded hover:bg-blue-50 text-left text-sm flex items-center justify-between group transition-colors">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate" x-text="theme.name"></div>
                                        <div class="text-xs text-gray-500"
                                            x-text="(theme.data || []).length + ' Blöcke'"></div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                        </path>
                                    </svg>
                                </button>
                            </template>
                            <div x-show="themes.length === 0" class="px-3 py-2 text-xs text-gray-500 text-center">
                                Keine Themes verfügbar
                            </div>
                        </div>
                    </div>`
        : `
                    <div class="mb-2">
                        <span class="text-sm font-semibold text-gray-800">Block-Typ wählen</span>
                    </div>
                    ${blocksPanel}`;

    return `
        <!-- Toolbar für neuen Block -->
        <div x-show="showToolbar" x-transition
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @click.self="closeBlockToolbar()">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col" @click.stop
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="flex justify-between items-center gap-3 p-6 border-b border-gray-200">
                    <div class="flex-1" x-show="showToolbarTab === 'blocks'">
                        <input
                            type="text"
                            x-model="toolbarSearchQuery"
                            placeholder="Block suchen..."
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900" x-show="showToolbarTab !== 'blocks'">Block hinzufügen</h2>
                    <button type="button" @click="closeBlockToolbar()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        title="Schließen">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-auto p-6">
                    ${tabbedBody}
                </div>
            </div>
        </div>
    `;
}
