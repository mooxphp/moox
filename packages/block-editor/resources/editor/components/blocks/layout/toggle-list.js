/**
 * Toggle List Block Component
 * Überschrift (Absatz) mit Trigger rechts; Inhalt kann weitere Block-Komponenten enthalten.
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const ToggleListBlock = {
    type: 'toggleList',

    options: BLOCK_TYPES.toggleList,

    structure: {
        id: '',
        type: 'toggleList',
        content: '',
        expanded: true,
        children: [],
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },

    renderHTML(block, context = {}) {
        return this.renderToggleListHTML('block', block, context);
    },

    renderChildHTML(child, context = {}) {
        return this.renderToggleListHTML('child', child, context);
    },

    renderToggleListHTML(scope, data, context = {}) {
        const blockId = data.id || '';
        const { selectedBlockId, draggingBlockId, childBlockTypes, index, addComponentsEnabled } = context;
        const placeholder = this.options.placeholder || 'Überschrift...';
        const isChild = scope === 'child';
        const enterHandler = isChild
            ? 'handleToggleListHeadingEnterChild(block.id, childIndex, child.id, $event)'
            : 'handleToggleListHeadingEnter(block.id, $event)';

        return `
            <div x-show="${scope}.type === 'toggleList'" 
                 x-data="{ componentBlock: ${scope} }"
                 :id="${scope}.htmlId || null"
                 :style="${scope}.style || ''"
                 :class="['border border-gray-300 rounded-lg', ${scope}.classes || '']">
                <!-- Zeile: Überschrift links, Trigger rechts -->
                <div class="flex items-center gap-2 w-full min-h-[2.5rem] px-3 py-2 bg-gray-50 border-b border-gray-200">
                    <div 
                        class="flex-1 min-w-0 block-placeholder min-h-[1.5rem] text-sm"
                        data-block-id="${blockId}"
                        contenteditable="true"
                        data-placeholder="${placeholder}"
                        x-init="$nextTick(() => initBlockContent($el, ${scope}))"
                        @paste.prevent="handlePlainTextPaste($event)"
                        @input="updateBlockContent(${scope}.id, $event.target.innerHTML)"
                        @blur="commitBlockContent(${scope}.id, $event.target.innerHTML)"
                        @keydown.enter.prevent="${enterHandler}"
                        @focus="initBlockContent($event.target, ${scope})"
                    ></div>
                    <button type="button"
                        type="button"
                        @click.stop="toggleListExpanded(${scope}.id)"
                        class="flex-shrink-0 p-2 rounded hover:bg-gray-200 text-gray-600 transition-colors"
                        :title="${scope}.expanded !== false ? 'Inhalt einklappen' : 'Inhalt aufklappen'"
                        aria-label="${scope}.expanded !== false ? 'Einklappen' : 'Aufklappen'"
                    >
                        <svg x-show="${scope}.expanded !== false" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                        <svg x-show="${scope}.expanded === false" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                </div>
                <!-- Inhalt (Kinder-Blöcke) -->
                <div x-show="componentBlock.expanded !== false" 
                     class="space-y-2 p-4 border-t-0 border-gray-200 bg-white">
                    <template x-for="(childItem, childIndex) in (componentBlock.children || [])" :key="childItem.id">
                        <div 
                            :class="{
                                'ring-2 ring-blue-500': typeof selectedBlockId !== 'undefined' && selectedBlockId === childItem.id,
                                'opacity-50': typeof draggingBlockId !== 'undefined' && draggingBlockId === childItem.id,
                                'drag-over': typeof dragOverIndex !== 'undefined' && dragOverIndex && dragOverIndex.type === 'child' &&
                                    dragOverIndex.parentIndex === index &&
                                    dragOverIndex.childIndex === childIndex
                            }"
                            class="block-item group relative p-2 rounded-lg hover:bg-gray-50 transition-all "
                            @click.stop="typeof selectBlock === 'function' && selectBlock(childItem.id)" draggable="true"
                            @dragstart="typeof handleChildDragStart === 'function' && handleChildDragStart($event, componentBlock.id, childIndex)"
                            @dragover.prevent="typeof handleChildDragOver === 'function' && handleChildDragOver($event, componentBlock.id, childIndex)"
                            @drop="typeof handleChildDrop === 'function' && handleChildDrop($event, componentBlock.id, childIndex)"
                            @dragend="typeof handleDragEnd === 'function' && handleDragEnd()"
                        >
                            <div x-show="typeof selectedBlockId !== 'undefined' && selectedBlockId === childItem.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                            <div :class="typeof selectedBlockId !== 'undefined' && selectedBlockId === childItem.id ? 'opacity-100' : ''" class="absolute -left-16 top-2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                <div class="relative" x-data="{ showChildMenu: false }">
                                    <button type="button" @click.stop="showChildMenu = !showChildMenu"
                                        class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100 text-xs"
                                        title="Block-Menü">
                                        ⋯
                                    </button>
                                    <div x-show="showChildMenu" @click.outside="showChildMenu = false" x-transition
                                        class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                        <button
                                            type="button" @click.stop="typeof openSidebar === 'function' && openSidebar(childItem.id); showChildMenu = false"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                            title="Einstellungen öffnen">
                                            <span>⚙</span>
                                            <span>Einstellungen</span>
                                        </button>
                                        <button
                                            type="button" @click.stop="typeof deleteBlock === 'function' && deleteBlock(childItem.id); showChildMenu = false"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                            title="Löschen">
                                            <span>×</span>
                                            <span>Löschen</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div :class="typeof selectedBlockId !== 'undefined' && selectedBlockId === childItem.id ? 'opacity-100' : ''" class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                <button type="button" @click.stop="typeof moveChildBlock === 'function' && moveChildBlock(componentBlock.id, childIndex, 'up')"
                                    :disabled="childIndex === 0"
                                    :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach oben">
                                    ↑
                                </button>
                                <button type="button" @click.stop="typeof moveChildBlock === 'function' && moveChildBlock(componentBlock.id, childIndex, 'down')"
                                    :disabled="childIndex === ((componentBlock.children || []).length - 1)"
                                    :class="childIndex === ((componentBlock.children || []).length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach unten">
                                    ↓
                                </button>
                            </div>
                            <div class="block-content">
                                <div
                                    x-html="typeof renderChild === 'function' ? renderChild(childItem, componentBlock, childIndex) : ''"
                                    x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                    x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                ></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="typeof addComponentsEnabled !== 'undefined' && addComponentsEnabled" class="flex items-center gap-2 pt-1">
                        <select 
                            @change.stop="typeof addChild === 'function' && addChild(componentBlock.id, $event.target.value); $event.target.value = ''"
                            @click.stop
                            class="px-3 py-1 text-sm border border-gray-300 rounded bg-white"
                        >
                            <option value="">+ Block hinzufügen</option>
                            <template x-for="(config, blockType) in (typeof childBlockTypes !== 'undefined' ? childBlockTypes : {})" :key="blockType">
                                <option :value="blockType" x-text="config.label || blockType"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        `;
    },

    initialize(block, blockIdCounter) {
        if (block.expanded === undefined) block.expanded = true;
        if (!block.children) block.children = [];
        if (block.content === undefined) block.content = '';
        return block;
    },

    ensureInitialized(block, blockIdCounter) {
        if (block.expanded === undefined) block.expanded = true;
        if (!block.children) block.children = [];
        if (block.content === undefined) block.content = '';
        return block;
    },

    cleanup(block) {
        if (block.expanded !== undefined) delete block.expanded;
        if (block.children) delete block.children;
        return block;
    },

    focusable: false
};
