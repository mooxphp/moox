/**
 * Quote Block Component
 * Enthält alle Informationen für Quote-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const QuoteBlock = {
    type: 'quote',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.quote,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'quote',
        content: '',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    renderHTML(block, context = {}) {
        return this.renderQuoteHTML('block', block);
    },
    
    renderChildHTML(child, context = {}) {
        return this.renderQuoteHTML('child', child);
    },
    
    renderQuoteHTML(scope, data) {
        const isChild = scope === 'child';
        const placeholder = this.options.placeholder || 'Zitat...';
        const addAfterCall = isChild
            ? "addChildAfter((column && column.id) || (block && block.id), childIndex, 'quote')"
            : "addBlockAfter(block.id, 'quote')";
        
        return `
            <div x-show="${scope}.type === 'quote'">
                <blockquote 
                    :data-block-id="${scope}.id"
                    :id="${scope}.htmlId || null"
                    :style="${scope}.style || ''"
                    :class="['block-placeholder border-l-4 border-gray-300 pl-4 italic text-gray-600', ${scope}.classes || '']"
                    contenteditable="true"
                    data-placeholder="${placeholder}"
                    x-init="$nextTick(() => initBlockContent($el, ${scope}))"
                    @paste.prevent="handlePlainTextPaste($event)"
                    @input="updateBlockContent(${scope}.id, $event.target.innerHTML)"
                    @blur="commitBlockContent(${scope}.id, $event.target.innerHTML)"
                    @keydown.enter.prevent="${addAfterCall}"
                    @keydown.backspace="handleBackspace(${scope}.id, $event)"
                    @focus="initBlockContent($event.target, ${scope})"
                ></blockquote>
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
    
    // Fokus-Verhalten: Standard-Implementierung für editierbare Blöcke
    focusable: true,
    focus(element, block) {
        if (!element) return false;
        if (element.hasAttribute('contenteditable') && element.getAttribute('contenteditable') === 'true') {
            element.focus();
            try {
                const range = document.createRange();
                const sel = window.getSelection();
                if (sel) {
                    range.selectNodeContents(element);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            } catch (error) {
                console.warn('Fehler beim Setzen des Cursors:', error);
            }
            return true;
        }
        return false;
    }
};
