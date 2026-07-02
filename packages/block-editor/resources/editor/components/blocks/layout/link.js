/**
 * Link Block Component
 * Enthält alle Informationen für Link-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const LinkBlock = {
    type: 'link',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.link,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'link',
        content: '',
        linkUrl: '',
        linkText: '',
        linkTarget: '_blank',
        style: '',
        classes: '',
        htmlId: '',
        children: [],
        createdAt: '',
        updatedAt: ''
    },
    
    renderHTML(block, context = {}) {
        return this.renderLinkHTML('block', block, context);
    },
    
    renderChildHTML(child, context = {}) {
        return this.renderLinkHTML('child', child, context);
    },
    
    renderLinkHTML(scope, data, context = {}) {
        const linkUrl = data.linkUrl || '';
        const linkText = data.linkText || data.content || linkUrl;
        const linkTarget = data.linkTarget || '_blank';
        const { selectedBlockId, draggingBlockId, childBlockTypes, index, addComponentsEnabled } = context;
        
        return `
            <div x-show="${scope}.type === 'link'" class="space-y-3">
                <a 
                    :data-block-id="${scope}.id"
                    :id="${scope}.htmlId || null"
                    :href="${scope}.linkUrl || '#'"
                    :target="${scope}.linkTarget || '_blank'"
                    :rel="(${scope}.linkTarget || '_blank') === '_blank' ? 'noopener noreferrer' : null"
                    :style="${scope}.style || ''"
                    :class="['inline-flex items-center gap-2 text-blue-600 underline', ${scope}.classes || '']"
                    @click.prevent="openLinkModal('block', { blockId: ${scope}.id })"
                >
                    <span x-text="getLinkTypeIcon(${scope}.linkUrl || '')" class="text-base leading-none" aria-hidden="true"></span>
                    <span x-text="${scope}.linkText || ${scope}.content || ${scope}.linkUrl || 'Link'"></span>
                </a>
                
                <!-- Children Blocks -->
                <div class="space-y-2 border-l-2 border-gray-300 pl-4">
                    <template x-for="(child, childIndex) in (${scope}.children || [])" :key="child.id">
                        <div 
                            :class="{
                                'ring-2 ring-blue-500': typeof selectedBlockId !== 'undefined' && selectedBlockId === child.id,
                                'opacity-50': typeof draggingBlockId !== 'undefined' && draggingBlockId === child.id,
                                'drag-over': typeof dragOverIndex !== 'undefined' && dragOverIndex && dragOverIndex.type === 'child' &&
                                    dragOverIndex.parentIndex === index &&
                                    dragOverIndex.childIndex === childIndex
                            }"
                            class="block-item group relative p-2 rounded-lg hover:bg-gray-50 transition-all "
                            @click.stop="typeof selectBlock === 'function' && selectBlock(child.id)" draggable="true"
                            @dragstart="typeof handleChildDragStart === 'function' && handleChildDragStart($event, ${scope}.id, childIndex)"
                            @dragover.prevent="typeof handleChildDragOver === 'function' && handleChildDragOver($event, ${scope}.id, childIndex)"
                            @drop="typeof handleChildDrop === 'function' && handleChildDrop($event, ${scope}.id, childIndex)"
                            @dragend="typeof handleDragEnd === 'function' && handleDragEnd()"
                        >
                            <div x-show="typeof selectedBlockId !== 'undefined' && selectedBlockId === child.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                            <div :class="typeof selectedBlockId !== 'undefined' && selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -left-16 top-2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                <div class="relative" x-data="{ showChildMenu: false }">
                                    <button type="button" @click.stop="showChildMenu = !showChildMenu"
                                        class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100 text-xs"
                                        title="Block-Menü">
                                        ⋯
                                    </button>
                                    <div x-show="showChildMenu" @click.outside="showChildMenu = false" x-transition
                                        class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                        <button
                                            type="button" @click.stop="typeof openSidebar === 'function' && openSidebar(child.id); showChildMenu = false"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                            title="Einstellungen öffnen">
                                            <span>⚙</span>
                                            <span>Einstellungen</span>
                                        </button>
                                        <button
                                            type="button" @click.stop="typeof deleteBlock === 'function' && deleteBlock(child.id); showChildMenu = false"
                                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                            title="Löschen">
                                            <span>×</span>
                                            <span>Löschen</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div :class="typeof selectedBlockId !== 'undefined' && selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                <button type="button" @click.stop="typeof moveChildBlock === 'function' && moveChildBlock(${scope}.id, childIndex, 'up')"
                                    :disabled="childIndex === 0"
                                    :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach oben">
                                    ↑
                                </button>
                                <button type="button" @click.stop="typeof moveChildBlock === 'function' && moveChildBlock(${scope}.id, childIndex, 'down')"
                                    :disabled="childIndex === ((typeof ${scope} !== 'undefined' && Array.isArray(${scope}.children)) ? ${scope}.children.length : 0) - 1"
                                    :class="childIndex === ((typeof ${scope} !== 'undefined' && Array.isArray(${scope}.children)) ? ${scope}.children.length : 0) - 1 ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach unten">
                                    ↓
                                </button>
                            </div>
                            <div class="block-content">
                                <div
                                    x-html="typeof renderChild === 'function' ? renderChild(child, ${scope}, childIndex) : ''"
                                    x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                    x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                ></div>
                            </div>
                        </div>
                    </template>
                    <div x-show="typeof addComponentsEnabled !== 'undefined' && addComponentsEnabled" class="flex items-center gap-2">
                        <select 
                            @change.stop="typeof addChild === 'function' && addChild(${scope}.id, $event.target.value); $event.target.value = ''"
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
        if (!block.linkUrl) block.linkUrl = '';
        if (!block.linkText) block.linkText = '';
        if (!block.linkTarget) block.linkTarget = '_blank';
        if (!block.children) block.children = [];
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (!block.linkUrl) block.linkUrl = '';
        if (!block.linkText) block.linkText = '';
        if (!block.linkTarget) block.linkTarget = '_blank';
        if (!block.children) block.children = [];
        return block;
    },
    
    cleanup(block) {
        delete block.linkUrl;
        delete block.linkText;
        delete block.linkTarget;
        return block;
    },
    
    // Fokus-Verhalten: Link ist nicht automatisch fokussierbar
    focusable: false
};
