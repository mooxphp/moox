/**
 * Group Block Component
 * Enthält alle Informationen für Group Container-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const GroupBlock = {
    type: 'group',
    options: BLOCK_TYPES.group,
    structure: {
        id: '',
        type: 'group',
        children: [],
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    renderHTML(block, context = {}) {
        return this.renderColumnHTML('block', block, context);
    },
    renderChildHTML(child, context = {}) {
        return this.renderColumnHTML('child', child, context);
    },
    renderColumnHTML(scope, data, context = {}) {
        const columnCount = Array.isArray(data.children) && data.children.length > 0 ? data.children.length : 1;
        const gridStyle = `grid-template-columns: repeat(${columnCount}, minmax(0, 1fr));`;
        const { selectedBlockId, draggingBlockId, childBlockTypes, index, addComponentsEnabled } = context;

        return `
            <div x-show="${scope}.type === 'group'" 
                 :id="${scope}.htmlId || null"
                 :style="(${scope}.style || '') + '; ${gridStyle}'"
                 :class="['grid gap-4 p-4 border-2 border-dashed border-gray-300 rounded-lg min-h-[140px]', ${scope}.classes || '']">
                <template x-for="(column, columnIndex) in (${scope}.children || [])" :key="column.id">
                    <div class="space-y-2">
                        <template x-for="(child, childIndex) in (column.children || [])" :key="child.id">
                            <div 
                                :class="{
                                    'ring-2 ring-blue-500': selectedBlockId === child.id,
                                    'opacity-50': draggingBlockId === child.id,
                                    'drag-over': dragOverIndex && dragOverIndex.type === 'columnChild' && (
                                        (dragOverIndex.parentIndex === index || dragOverIndex.parentBlockId === ${scope}.id) &&
                                        dragOverIndex.columnIndex === columnIndex &&
                                        dragOverIndex.childIndex === childIndex
                                    )
                                }"
                                class="block-item group relative p-2 rounded-lg hover:bg-gray-50 transition-all bg-gray-50"
                                @click.stop="selectBlock(child.id)"
                                draggable="true"
                                @dragstart="handleColumnChildDragStart($event, ${scope}.id, columnIndex, childIndex)"
                                @dragover.prevent="handleColumnChildDragOver($event, ${scope}.id, columnIndex, childIndex)"
                                @drop="handleColumnChildDrop($event, ${scope}.id, columnIndex, childIndex)"
                                @dragend="handleDragEnd()"
                            >
                                <div x-show="selectedBlockId === child.id" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l"></div>
                                <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -left-16 top-2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                    <div class="relative" x-data="{ showColumnChildMenu: false }">
                                        <button type="button" @click.stop="showColumnChildMenu = !showColumnChildMenu"
                                            class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100 text-xs"
                                            title="Block-Menü">
                                            ⋯
                                        </button>
                                        <div x-show="showColumnChildMenu" @click.outside="showColumnChildMenu = false" x-transition
                                            class="absolute left-1/2 top-full mt-2 w-40 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                            <button
                                                @click.stop="openSidebar(child.id); showColumnChildMenu = false"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                                title="Einstellungen öffnen">
                                                <span>⚙</span>
                                                <span>Einstellungen</span>
                                            </button>
                                            <button
                                                @click.stop="deleteBlock(child.id); showColumnChildMenu = false"
                                                class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                                title="Löschen">
                                                <span>×</span>
                                                <span>Löschen</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div :class="selectedBlockId === child.id ? 'opacity-100' : ''" class="absolute -right-20 top-1/2 -translate-y-1/2 flex items-center gap-2 opacity-0 transition-opacity z-10">
                                    <button type="button" @click.stop="moveChildBlock(${scope}.id, childIndex, 'up', columnIndex)"
                                        :disabled="childIndex === 0"
                                        :class="childIndex === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach oben">
                                        ↑
                                    </button>
                                    <button type="button" @click.stop="moveChildBlock(${scope}.id, childIndex, 'down', columnIndex)"
                                        :disabled="childIndex === ((column.children || []).length - 1)"
                                        :class="childIndex === ((column.children || []).length - 1) ? 'opacity-50 cursor-not-allowed' : ''"
                                        class="flex h-7 w-7 items-center justify-center bg-white rounded shadow hover:bg-gray-100" title="Nach unten">
                                        ↓
                                    </button>
                                </div>
                                <div class="block-content">
                                    <div
                                        x-html="renderChild(child, column, childIndex)"
                                        x-init="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                        x-effect="$nextTick(() => window.Alpine && window.Alpine.initTree($el))"
                                    ></div>
                                </div>
                            </div>
                        </template>
                        <select
                            x-show="addComponentsEnabled"
                            @change.stop="addChildToColumn(${scope}.id, $event.target.value, columnIndex); $event.target.value = ''"
                            @click.stop
                            class="px-3 py-1 text-sm border border-gray-300 rounded bg-white w-full"
                        >
                            <option value="">+ Block hinzufügen</option>
                            <template x-for="(config, blockType) in childBlockTypes" :key="blockType">
                                <option :value="blockType" x-text="config.label || blockType"></option>
                            </template>
                        </select>
                    </div>
                </template>
            </div>
        `;
    },
    initialize(block, blockIdCounter) {
        if (!block.children || block.children.length < 1) {
            block.children = [{ id: `col-${block.id}-0`, type: 'column', children: [] }];
        }
        return block;
    },
    ensureInitialized(block, blockIdCounter) {
        if (!block.children || block.children.length < 1) {
            block.children = [{ id: `col-${block.id}-0`, type: 'column', children: [] }];
        }
        return block;
    },
    cleanup(block) {
        if (block.children) {
            delete block.children;
        }
        return block;
    },
    focusable: false
};
