/**
 * Tabs Block Component
 * Interaktiver Block mit mehreren Reitern und Content pro Reiter.
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { TabsManagement } from '../../../core/blocks/management.js';

export const TabsBlock = {
    type: 'tabs',

    options: BLOCK_TYPES.tabs,

    structure: {
        id: '',
        type: 'tabs',
        tabsData: null,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },

    renderHTML(block, context = {}) {
        return this.renderTabsHTML('block', block);
    },

    renderChildHTML(child, context = {}) {
        return this.renderTabsHTML('child', child);
    },

    renderTabsHTML(scope, data) {
        const blockId = data.id || '';
        return `
            <div x-show="${scope}.type === 'tabs'"
                 data-block-id="${blockId}"
                 :id="${scope}.htmlId || null"
                 :style="${scope}.style || ''"
                 :class="['border border-gray-300 rounded-lg', ${scope}.classes || '']">
                <div class="flex flex-wrap items-center gap-2 p-2 border-b border-gray-200 bg-gray-50">
                    <template x-for="(tab, tabIndex) in (${scope}.tabsData?.items || [])" :key="tab.id">
                        <button type="button"
                            @click.stop="setActiveTab('${blockId}', tab.id)"
                            :class="(${scope}.tabsData?.activeTabId === tab.id) ? 'bg-white text-blue-700 border-blue-200' : 'bg-gray-100 text-gray-700 border-transparent hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-md border text-sm transition-colors">
                            <span x-text="tab.title || ('Tab ' + (tabIndex + 1))"></span>
                        </button>
                    </template>
                    <button type="button"
                        @click.stop="addTabItem('${blockId}')"
                        class="px-2.5 py-1.5 rounded-md border border-dashed border-gray-300 text-gray-600 text-sm hover:bg-gray-100 transition-colors"
                        title="Tab hinzufügen">
                        +
                    </button>
                </div>

                <div class="p-4">
                    <template x-for="(tab, tabIndex) in (${scope}.tabsData?.items || [])" :key="'panel-' + tab.id">
                        <div x-show="${scope}.tabsData?.activeTabId === tab.id" class="space-y-3">
                            <div class="flex items-center gap-2">
                                <input type="text"
                                    :value="tab.title || ''"
                                    @input="updateTabTitle('${blockId}', tab.id, $event.target.value)"
                                    @blur="commitTabTitle('${blockId}', tab.id, $event.target.value)"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Tab-Titel...">
                                <button type="button"
                                    @click.stop="removeTabItem('${blockId}', tab.id)"
                                    :disabled="(${scope}.tabsData?.items || []).length <= 1"
                                    :class="{ 'opacity-50 cursor-not-allowed': ((${scope}.tabsData?.items || []).length <= 1) }"
                                    class="p-2 rounded hover:bg-red-50 text-red-600"
                                    title="Tab löschen">
                                    ×
                                </button>
                            </div>
                            <div class="space-y-2 p-3 border border-gray-200 rounded-lg bg-white">
                                <template x-for="(child, childIndex) in (tab.children || [])" :key="child.id">
                                    <div
                                        :class="{
                                            'ring-2 ring-blue-500': selectedBlockId === child.id,
                                            'opacity-50': draggingBlockId === child.id
                                        }"
                                        class="block-item group relative p-2 rounded-lg hover:bg-gray-50 transition-all"
                                        @click.stop="selectBlock(child.id)"
                                    >
                                        <div x-show="selectedBlockId === child.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -left-16 top-2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <div class="relative" x-data="{ showTabChildMenu: false }">
                                                <button type="button" @click.stop="showTabChildMenu = !showTabChildMenu"
                                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100 text-xs"
                                                    title="Block-Menü">
                                                    ⋯
                                                </button>
                                                <div x-show="showTabChildMenu" @click.outside="showTabChildMenu = false" x-transition
                                                    class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                                    <button type="button" @click.stop="openSidebar(child.id); showTabChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                                        title="Einstellungen öffnen">
                                                        <span>⚙</span>
                                                        <span>Einstellungen</span>
                                                    </button>
                                                    <button type="button" @click.stop="deleteBlock(child.id); showTabChildMenu = false"
                                                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                                        title="Löschen">
                                                        <span>×</span>
                                                        <span>Löschen</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                            <button type="button" @click.stop="moveTabChild('${blockId}', tab.id, childIndex, 'up')"
                                                :disabled="childIndex === 0"
                                                :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach oben">
                                                ↑
                                            </button>
                                            <button type="button" @click.stop="moveTabChild('${blockId}', tab.id, childIndex, 'down')"
                                                :disabled="childIndex === ((tab.children || []).length - 1)"
                                                :class="childIndex === ((tab.children || []).length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                                class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach unten">
                                                ↓
                                            </button>
                                        </div>
                                        <div class="block-content">
                                            <div
                                                x-html="renderChild(child, tab, childIndex)"
                                                x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                                x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                            ></div>
                                        </div>
                                    </div>
                                </template>

                                <select
                                    x-show="addComponentsEnabled"
                                    @change.stop="addChildToTab('${blockId}', tab.id, $event.target.value); $event.target.value = ''"
                                    @click.stop
                                    class="px-3 py-1 text-sm border border-gray-300 rounded bg-white w-full"
                                >
                                    <option value="">+ Block hinzufügen</option>
                                    <template x-for="(config, blockType) in childBlockTypes" :key="blockType">
                                        <option :value="blockType" x-text="config.label || blockType"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        `;
    },

    initialize(block, blockIdCounter) {
        const tabsData = TabsManagement.initializeTabs(blockIdCounter, 2);
        block.tabsData = tabsData;
        block.tabsData.lastTabIdCounter = tabsData.lastTabIdCounter;
        return block;
    },

    ensureInitialized(block, blockIdCounter) {
        if (!block.tabsData) {
            block.tabsData = TabsManagement.initializeTabs(blockIdCounter, 2);
        } else if (Array.isArray(block.tabsData.items)) {
            block.tabsData.items.forEach((item) => {
                if (!Array.isArray(item.children)) {
                    item.children = [];
                }
            });
        }
        return block;
    },

    cleanup(block) {
        if (block.tabsData) {
            delete block.tabsData;
        }
        return block;
    },

    focusable: false,

    getSettingsHTML(block, context = {}) {
        const tabsCount = block.tabsData?.items?.length || 0;
        return `
            <div class="pt-4 border-t border-gray-200 space-y-3">
                <div class="text-sm text-gray-600">Tabs: ${tabsCount}</div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="addTabItem(block.id)"
                        class="px-3 py-1 bg-gray-100 rounded text-sm hover:bg-gray-200">
                        Tab hinzufügen
                    </button>
                </div>
            </div>
        `;
    }
};
