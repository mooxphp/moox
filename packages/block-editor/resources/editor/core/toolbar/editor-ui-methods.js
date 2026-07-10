export const editorUiMethods = {
    openSidebar(blockId = null) {
        if (blockId) {
            this.selectedBlockId = blockId;
            // Setze Fokus auf den Block, wenn Sidebar geöffnet wird
            this.$nextTick(() => {
                this.focusBlockElement(blockId);
            });
        }
        this.showSidebar = true;
    },

    closeSidebar() {
        this.showSidebar = false;
        // this.selectedBlockId = null; // Optional: Block-Auswahl beibehalten
    },

    openBlockToolbar() {
        if (!this.addComponentsEnabled) {
            return;
        }
        this.showToolbar = true;
        this.showToolbarTab = 'blocks';
        this.toolbarSearchQuery = '';
        if (window.modalHelpers) window.modalHelpers.openModal();
    },

    closeBlockToolbar() {
        this.showToolbar = false;
        this.showToolbarTab = 'blocks';
        this.toolbarSearchQuery = '';
        if (window.modalHelpers) window.modalHelpers.closeModal();
    },

    matchesToolbarSearch(blockType, config = {}) {
        const query = String(this.toolbarSearchQuery || '').trim().toLowerCase();
        if (!query) {
            return true;
        }

        const label = String(config?.label || '').toLowerCase();
        const category = String(config?.category || '').toLowerCase();
        const type = String(blockType || '').toLowerCase();
        const searchableText = `${label} ${category} ${type}`;

        return searchableText.includes(query);
    },

    getBlocksOnlyToolbarTemplate() {
        return `
            <div x-show="showToolbar" x-transition
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="closeBlockToolbar()">
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col" @click.stop
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                    <div class="flex justify-between items-center gap-3 p-6 border-b border-gray-200">
                        <div class="flex-1">
                            <input
                                type="text"
                                x-model="toolbarSearchQuery"
                                placeholder="Block suchen..."
                                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <button type="button" @click="closeBlockToolbar()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Schließen">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto p-6">
                        <div class="mb-2">
                            <span class="text-sm font-semibold text-gray-800">Block-Typ wählen</span>
                        </div>
                        <div x-show="addComponentsEnabled" class="flex gap-2 flex-wrap">
                            <template x-for="[blockType, config] in Object.entries(childBlockTypes).filter(([type, cfg]) => matchesToolbarSearch(type, cfg))" :key="blockType">
                                <button type="button" @click="addBlock(blockType)"
                                    class="px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm transition-colors">
                                    <span x-text="config.label || blockType"></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="Object.entries(childBlockTypes).filter(([type, cfg]) => matchesToolbarSearch(type, cfg)).length === 0"
                            class="text-sm text-gray-500 py-3">
                            Keine Blöcke gefunden.
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    loadThemeFromToolbar(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.showLoadThemeConfirm(themeName);
    },
};
