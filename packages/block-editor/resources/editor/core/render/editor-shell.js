export function getEditorShellTemplate() {
    return `
    <div
        x-data="blockEditor()"
        x-init="init()"
        x-effect="syncLivewireState()"
        class="moox-block-editor flex"
    >
        <!-- Notification Component (aus Template) -->
        <div x-html="templates?.notification || ''"></div>



        <!-- Sidebar (aus Template) -->
        <div
            x-html="templates?.sidebar || ''"
            x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
            x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
        ></div>

    <!-- Main Content -->
    <div class="flex-1">
        <div class="w-full">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div class="flex gap-2">
                   
                    <button type="button" x-show="themeTemplatesEnabled" @click="openSaveThemeModal()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Theme speichern
                    </button>
                    <!-- Developer Tools (JSON) - bei Bedarf einkommentieren -->
                    <div x-html="templates?.developer?.headerActions || ''"></div>
                </div>
            </div>

            <!-- Editor Container -->
            <div id="editor-container" class="bg-white dark:bg-gray-900 rounded-lg shadow-lg dark:shadow-black/40 p-6 min-h-[600px]">
                <!-- Toolbar für neuen Block (aus Template) -->
                <div
                    x-show="addComponentsEnabled"
                    x-html="templates?.blockToolbar || ''"
                    x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                    x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                ></div>

                <!-- Blocks Container -->
                <div class="space-y-2" @click="deselectAll()">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <!-- Render Block with Children -->
                        <div>
                            <!-- Main Block -->
                            <div :class="{
                            'ring-2 ring-blue-500': selectedBlockId === block.id,
                            'opacity-50': draggingBlockId === block.id,
                            'drag-over': dragOverIndex && dragOverIndex.type === 'main' && dragOverIndex.index === index && (!dragStartIndex || dragStartIndex.index !== index)
                        }" class="block-item group relative p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                                @click.stop="selectBlock(block.id)" draggable="true"
                                @dragstart="handleDragStart($event, index)"
                                @dragover.prevent="handleDragOver($event, index)" @drop="handleDrop($event, index)"
                                @dragend="handleDragEnd()" @dragleave="dragOverIndex = null">
                                <!-- Visual Indicator für ausgewählten Block -->
                                <div x-show="selectedBlockId === block.id"
                                    class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                                <!-- Block Controls (links) -->
                                <div :class="selectedBlockId === block.id ? 'opacity-100' : ''"
                                    class="absolute -left-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-20">
                                    <button type="button" x-show="addComponentsEnabled" @click.stop="openBlockToolbar()"
                                        class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700 text-xs"
                                        title="Block hinzufügen">
                                        +
                                    </button>
                                    <div class="relative" x-data="{ showBlockMenu: false }">
                                        <button type="button" @click.stop="showBlockMenu = !showBlockMenu"
                                            class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700 text-xs"
                                            title="Block-Menü">
                                            ⋯
                                        </button>
                                        <div x-show="showBlockMenu" @click.outside="showBlockMenu = false" x-transition
                                            class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shadow-lg">
                                            <button type="button"
                                                @click.stop="openSidebar(block.id); showBlockMenu = false"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Einstellungen öffnen">
                                                <span>⚙</span>
                                                <span>Einstellungen</span>
                                            </button>
                                            <button type="button"
                                                @click.stop="deleteBlock(block.id); showBlockMenu = false"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                title="Löschen">
                                                <span>×</span>
                                                <span>Löschen</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Move Controls (rechts) -->
                                <div :class="selectedBlockId === block.id ? 'opacity-100' : ''"
                                    class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                    <button type="button" @click.stop="moveBlock(index, 'up')" :disabled="index === 0"
                                        :class="index === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Nach oben">
                                        ↑
                                    </button>
                                    <button type="button" @click.stop="moveBlock(index, 'down')"
                                        :disabled="index === blocks.length - 1"
                                        :class="index === blocks.length - 1 ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700"
                                        title="Nach unten">
                                        ↓
                                    </button>
                                </div>

                                <!-- Block Content -->
                                <div class="block-content">
                                    <!-- Zentrale Rendering-Funktion für alle Block-Typen -->
                                    <div
                                        x-html="renderBlock(block, index)"
                                        x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                        x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                    ></div>

                                </div>
                            </div>

                            <!-- Children Blocks (Verschachtelt) - für normale Container -->
                            <div x-show="block.children && block.children.length > 0 && !isColumnLikeBlock(block.type) && block.type !== 'link' && block.type !== 'toggleList'"
                                class="ml-8 mt-2 space-y-2 border-l-2 border-gray-300 dark:border-gray-700 pl-4">
                                <template x-for="(child, childIndex) in block.children" :key="child.id">
                                    <div :class="{
                                        'ring-2 ring-blue-500': selectedBlockId === child.id,
                                        'opacity-50': draggingBlockId === child.id,
                                        'drag-over': dragOverIndex && dragOverIndex.type === 'child' && dragOverIndex.parentIndex === index && dragOverIndex.childIndex === childIndex && (!dragStartIndex || dragStartIndex.childIndex !== childIndex)
                                    }" class="block-item group relative p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-all bg-gray-50 dark:bg-gray-800/60"
                                        @click.stop="selectBlock(child.id)" draggable="true"
                                        @dragstart="handleDragStart($event, index, childIndex)"
                                        @dragover.prevent="handleDragOver($event, index, childIndex)"
                                        @drop="handleDrop($event, index, childIndex)" @dragend="handleDragEnd()">
                                        <!-- Visual Indicator für ausgewählten Child Block -->
                                        <div x-show="selectedBlockId === child.id"
                                            class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>

                                        <!-- Child Block Controls (links) -->
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''"
                                            class="absolute -left-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <button type="button" x-show="addComponentsEnabled" @click.stop="openBlockToolbar()"
                                                class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700 text-xs"
                                                title="Block hinzufügen">
                                                +
                                            </button>
                                            <div class="relative" x-data="{ showChildMenu: false }">
                                                <button type="button" @click.stop="showChildMenu = !showChildMenu"
                                                    class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700 text-xs"
                                                    title="Block-Menü">
                                                    ⋯
                                                </button>
                                                <div x-show="showChildMenu" @click.outside="showChildMenu = false" x-transition
                                                    class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1 shadow-lg">
                                                    <button type="button"
                                                        @click.stop="openSidebar(child.id); showChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                        title="Einstellungen öffnen">
                                                        <span>⚙</span>
                                                        <span>Einstellungen</span>
                                                    </button>
                                                    <button type="button"
                                                        @click.stop="deleteBlock(child.id); showChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                        title="Löschen">
                                                        <span>×</span>
                                                        <span>Löschen</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Child Move Controls (rechts) -->
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''"
                                            class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <button type="button" @click.stop="moveChildBlock(block.id, childIndex, 'up')"
                                                :disabled="childIndex === 0"
                                                :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Nach oben">
                                                ↑
                                            </button>
                                            <button type="button" @click.stop="moveChildBlock(block.id, childIndex, 'down')"
                                                :disabled="childIndex === (block.children.length - 1)"
                                                :class="childIndex === (block.children.length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white dark:bg-gray-800 dark:text-gray-100 rounded shadow hover:bg-gray-100 dark:hover:bg-gray-700"
                                                title="Nach unten">
                                                ↓
                                            </button>
                                        </div>

                                        <!-- Child Block Content -->
                                        <div class="block-content">
                                            <!-- Zentrale Rendering-Funktion für alle Child-Block-Typen -->
                                            <div
                                                x-html="renderChild(child, block, childIndex)"
                                                x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                                x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                            ></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <!-- Developer Tools (JSON) -->
            <div x-html="templates?.developer?.jsonDisplay || ''"></div>
        </div>

        <!-- JSON Import Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showImportModal">
            <div x-html="templates?.modals?.jsonImport || ''"></div>
        </template>

        <!-- Save Theme Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showSaveThemeModal">
            <div x-html="templates?.modals?.themeSave || ''"></div>
        </template>

        <!-- Edit Theme Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showEditThemeModal">
            <div x-html="templates?.modals?.themeEdit || ''"></div>
        </template>

        <!-- Import Theme Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showImportThemeModal">
            <div x-html="templates?.modals?.themeImport || ''"></div>
        </template>

        <!-- Floating Toolbar für Text-Selektion (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showFloatingToolbar">
            <div x-html="templates?.floatingToolbar || ''"></div>
        </template>

        <!-- Einheitliches Link-Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showLinkModal">
            <div x-html="templates?.modals?.link || ''"></div>
        </template>

        <!-- Confirm Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showConfirmModal">
            <div x-html="templates?.modals?.confirm || ''"></div>
        </template>

        <!-- Image Settings Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showImageSettingsModal">
            <div x-html="templates?.modals?.imageSettings || ''"></div>
        </template>

        <!-- Video Settings Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showVideoSettingsModal">
            <div x-html="templates?.modals?.videoSettings || ''"></div>
        </template>

        <!-- Embed Settings Modal (aus Template) - Lazy Loading mit x-if -->
        <template x-if="showEmbedSettingsModal">
            <div x-html="templates?.modals?.embedSettings || ''"></div>
        </template>

    </div>
    </div>
    `;
}
