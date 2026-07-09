/**
 * Checklist Block Component
 * Enthält alle Informationen für Checklist-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';
import { ChecklistManagement } from '../../../core/blocks/management.js';

export const ChecklistBlock = {
    type: 'checklist',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.checklist,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'checklist',
        checklistData: null,
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    renderHTML(block, context = {}) {
        return this.renderChecklistHTML('block', block);
    },
    
    // Child-Version (für verschachtelte Checklisten)
    renderChildHTML(child, context = {}) {
        return this.renderChecklistHTML('child', child);
    },
    
    renderChecklistHTML(scope, data) {
        const blockId = data.id || '';
        const items = data.checklistData?.items || [];
        const itemsLength = items.length;
        
        return `
            <div x-show="${scope}.type === 'checklist'" 
                 data-block-id="${blockId}"
                 :id="${scope}.htmlId || null"
                 :style="${scope}.style || ''"
                 :class="['space-y-2 p-4 border border-gray-300 rounded-lg', ${scope}.classes || '']">
                <template x-for="(item, itemIndex) in (${scope}.checklistData?.items || [])" :key="item.id">
                    <div class="flex items-start gap-3 group hover:bg-gray-50 p-2 rounded transition-colors">
                        <!-- Checkbox -->
                        <input 
                            type="checkbox"
                            :checked="item.checked || false"
                            @change="toggleChecklistItem('${blockId}', itemIndex)"
                            @click.stop
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                        />
                        <!-- Text Input -->
                        <div 
                            :data-item-id="item.id"
                            data-block-id="${blockId}"
                            :data-item-index="itemIndex"
                            class="flex-1 block-placeholder min-h-[1.5rem] text-sm"
                            contenteditable="true"
                            data-placeholder="Checkliste-Eintrag..."
                            x-init="$nextTick(() => { if (item.text !== undefined && item.text !== null) { $el.innerHTML = $sanitizeHtml(item.text || ''); } })"
                            @paste.prevent="handlePlainTextPaste($event)"
                            @input="updateChecklistItemText('${blockId}', item.id, $event.target.innerHTML)"
                            @blur="commitChecklistItemText('${blockId}', item.id, $event.target.innerHTML)"
                            @keydown.enter.prevent="addChecklistItem('${blockId}', 'bottom')"
                            @keydown.backspace="if (!$el.textContent.trim() && ${itemsLength} > 1) { $event.preventDefault(); removeChecklistItem('${blockId}', itemIndex); }"
                            @focus="initBlockContent($event.target, { content: item.text || '' })"
                        ></div>
                        <!-- Actions -->
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button"
                                @click.stop="moveChecklistItemUp('${blockId}', itemIndex)"
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
                                @click.stop="moveChecklistItemDown('${blockId}', itemIndex)"
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
                                @click.stop="removeChecklistItem('${blockId}', itemIndex)"
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
        const checklistData = ChecklistManagement.initializeChecklist(blockIdCounter, 1);
        block.checklistData = checklistData;
        block.checklistData.lastItemIdCounter = checklistData.lastItemIdCounter;
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        if (!block.checklistData) {
            block.checklistData = ChecklistManagement.initializeChecklist(blockIdCounter, 1);
        }
        return block;
    },
    
    cleanup(block) {
        // Entferne checklistData beim Typ-Wechsel
        if (block.checklistData) {
            delete block.checklistData;
        }
        return block;
    },
    
    // Fokus-Verhalten: Checklist ist nicht automatisch fokussierbar
    focusable: false
};
