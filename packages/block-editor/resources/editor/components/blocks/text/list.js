/**
 * List Block Component
 * Enthält alle Informationen für Listen-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { ListManagement } from '../../../core/blocks/management.js';

export const ListBlock = {
    type: 'list',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.list,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'list',
        listData: null,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    renderHTML(block, context = {}) {
        return this.renderListHTML('block', block);
    },
    
    // Child-Version (für verschachtelte Listen)
    renderChildHTML(child, context = {}) {
        return this.renderListHTML('child', child);
    },
    
    renderListHTML(scope, data) {
        const blockId = data.id || '';
        const items = data.listData?.items || [];
        const itemsLength = items.length;
        const listStyle = data.listData?.listStyle || 'unordered';
        const isOrdered = listStyle === 'ordered';
        
        return `
            <div x-show="${scope}.type === 'list'" 
                 data-block-id="${blockId}"
                 :id="${scope}.htmlId || null"
                 :style="${scope}.style || ''"
                 :class="['space-y-2 p-4 border border-gray-300 rounded-lg', ${scope}.classes || '']">
                <template x-for="(item, itemIndex) in (${scope}.listData?.items || [])" :key="item.id">
                    <div class="flex items-start gap-3 group hover:bg-gray-50 p-2 rounded transition-colors">
                        <!-- Marker -->
                        <div class="mt-0.5 w-6 text-right text-gray-500 text-sm select-none">
                            <span x-text="${isOrdered ? '(itemIndex + 1) + \'.\'' : '\'-\''}"></span>
                        </div>
                        <!-- Text Input -->
                        <div 
                            :data-item-id="item.id"
                            data-block-id="${blockId}"
                            :data-item-index="itemIndex"
                            class="flex-1 block-placeholder min-h-[1.5rem] text-sm"
                            contenteditable="true"
                            data-placeholder="Listen-Eintrag..."
                            x-init="$nextTick(() => { if (item.text !== undefined && item.text !== null) { $el.innerHTML = $sanitizeHtml(item.text || ''); } })"
                            @input="updateListItemText('${blockId}', item.id, $event.target.innerHTML)"
                            @blur="commitListItemText('${blockId}', item.id, $event.target.innerHTML)"
                            @keydown.enter.prevent="addListItem('${blockId}', 'bottom')"
                            @keydown.backspace="if (!$el.textContent.trim() && ${itemsLength} > 1) { $event.preventDefault(); removeListItem('${blockId}', itemIndex); }"
                            @focus="initBlockContent($event.target, { content: item.text || '' })"
                        ></div>
                        <!-- Actions -->
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button"
                                @click.stop="moveListItemUp('${blockId}', itemIndex)"
                                :disabled="itemIndex === 0"
                                :class="{'opacity-50 cursor-not-allowed': itemIndex === 0}"
                                class="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded"
                                title="Nach oben verschieben"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                            <button type="button"
                                @click.stop="moveListItemDown('${blockId}', itemIndex)"
                                :disabled="itemIndex === ${itemsLength > 0 ? itemsLength - 1 : 0}"
                                :class="{'opacity-50 cursor-not-allowed': itemIndex === ${itemsLength > 0 ? itemsLength - 1 : 0}}"
                                class="p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded"
                                title="Nach unten verschieben"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <button type="button"
                                @click.stop="removeListItem('${blockId}', itemIndex)"
                                :disabled="${itemsLength <= 1}"
                                :class="{'opacity-50 cursor-not-allowed': ${itemsLength <= 1}}"
                                class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded"
                                title="Eintrag löschen"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
                <!-- Empty State -->
                <div x-show="${itemsLength === 0}" 
                     class="text-center text-gray-400 py-4 text-sm">
                    Keine Einträge vorhanden
                </div>
            </div>
        `;
    },
    
    // Initialisierung
    initialize(block, blockIdCounter) {
        const listData = ListManagement.initializeList(blockIdCounter, 1, 'unordered');
        block.listData = listData;
        block.listData.lastItemIdCounter = listData.lastItemIdCounter;
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (!block.listData) {
            block.listData = ListManagement.initializeList(blockIdCounter, 1, 'unordered');
        }
        return block;
    },
    
    cleanup(block) {
        // Entferne listData beim Typ-Wechsel
        if (block.listData) {
            delete block.listData;
        }
        return block;
    },
    
    // Fokus-Verhalten: List ist nicht automatisch fokussierbar
    focusable: false,

    // Einstellungen für Sidebar
    getSettingsHTML(block, context = {}) {
        const listStyle = block.listData?.listStyle || 'unordered';
        return `
            <div class="pt-4 border-t border-gray-200">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Listen-Typ:
                </label>
                <select
                    x-model="block.listData.listStyle"
                    @change="setListStyle(block.id, block.listData.listStyle)"
                    class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="unordered" ${listStyle === 'unordered' ? 'selected' : ''}>Ungeordnet</option>
                    <option value="ordered" ${listStyle === 'ordered' ? 'selected' : ''}>Nummeriert</option>
                </select>
            </div>
        `;
    }
};
