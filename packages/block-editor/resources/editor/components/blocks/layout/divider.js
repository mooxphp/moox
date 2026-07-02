/**
 * Divider Block Component
 * Enthält alle Informationen für Divider-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const DividerBlock = {
    type: 'divider',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.divider,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'divider',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    renderHTML(block, context = {}) {
        return this.renderDividerHTML('block', block);
    },
    
    renderChildHTML(child, context = {}) {
        return this.renderDividerHTML('child', child);
    },
    
    renderDividerHTML(scope, data) {
        return `
            <div x-show="${scope}.type === 'divider'" class="py-2">
                <hr 
                    :data-block-id="${scope}.id"
                    :id="${scope}.htmlId || null"
                    :style="${scope}.style || ''"
                    :class="['border-t border-gray-300', ${scope}.classes || '']"
                />
            </div>
        `;
    },
    
    initialize(block, blockIdCounter) {
        return block;
    },
    
    ensureInitialized(block, blockIdCounter) {
        return block;
    },
    
    cleanup(block) {
        return block;
    },
    
    // Fokus-Verhalten: Divider sind nicht fokussierbar
    focusable: false,
    
    // Setzt den Fokus auf das Block-Element (für Divider: scrollIntoView)
    // @param {HTMLElement} element - Das DOM-Element des Blocks
    // @param {object} block - Der Block-Objekt
    focus(element, block) {
        if (!element) return false;
        
        // Für Divider: Scrolle zum Element
        if (element.scrollIntoView) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return true;
    }
};
