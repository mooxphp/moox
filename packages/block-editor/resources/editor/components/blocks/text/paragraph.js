/**
 * Paragraph Block Component
 * Enthält alle Informationen für Paragraph-Blöcke an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

export const ParagraphBlock = {
    // Block-Typ Name
    type: 'paragraph',
    
    // Konfiguration direkt aus BLOCK_TYPES
    options: BLOCK_TYPES.paragraph,
    
    // Datenstruktur-Definition
    structure: {
        id: '',
        type: 'paragraph',
        content: '',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: ''
    },
    
    // HTML-Template für Rendering
    renderHTML(block, context = {}) {
        return this.renderParagraphHTML('block', block, context);
    },
    
    // Child-Version (für verschachtelte Blöcke)
    renderChildHTML(child, context = {}) {
        return this.renderParagraphHTML('child', child, context);
    },
    
    renderParagraphHTML(scope, data, context = {}) {
        const isChild = scope === 'child';
        const placeholder = this.options.placeholder || 'Schreibe einen Absatz...';
        const addAfterCall = isChild
            ? "addChildAfter((column && column.id) || (block && block.id), childIndex, 'paragraph')"
            : "addBlockAfter(block.id, 'paragraph')";
        
        return `
            <div x-show="${scope}.type === 'paragraph'">
                <div 
                    :data-block-id="${scope}.id"
                    :id="${scope}.htmlId || null"
                    :style="${scope}.style || ''"
                    :class="['block-placeholder min-h-[1.5rem]', ${scope}.classes || '']"
                    contenteditable="true"
                    data-placeholder="${placeholder}"
                    x-init="$nextTick(() => initBlockContent($el, ${scope}))"
                    @paste.prevent="handlePlainTextPaste($event)"
                    @input="updateBlockContent(${scope}.id, $event.target.innerHTML)"
                    @blur="commitBlockContent(${scope}.id, $event.target.innerHTML)"
                    @keydown="handleQuickListShortcut(${scope}.id, $event)"
                    @keydown.enter.prevent="${addAfterCall}"
                    @keydown.backspace="handleBackspace(${scope}.id, $event)"
                    @focus="initBlockContent($event.target, ${scope})"
                ></div>
            </div>
        `;
    },
    
    // Initialisierung (aus block-components.js)
    initialize(block, blockIdCounter) {
        // Standard-Blöcke benötigen keine spezielle Initialisierung
        return block;
    },
    
    // Sicherstellen dass Block initialisiert ist
    ensureInitialized(block, blockIdCounter) {
        // Standard-Blöcke sind immer initialisiert
        return block;
    },
    
    // Cleanup beim Typ-Wechsel
    cleanup(block) {
        // Keine Cleanup-Logik für Standard-Blöcke
        return block;
    },
    
    // Einstellungen HTML für Sidebar (Standard: keine speziellen Einstellungen)
    getSettingsHTML(block, context = {}) {
        return '';
    },
    
    // Fokus-Verhalten: Standard-Implementierung für editierbare Blöcke
    // Gibt true zurück, wenn der Block fokussierbar ist
    focusable: true,
    
    // Setzt den Fokus auf das Block-Element
    // @param {HTMLElement} element - Das DOM-Element des Blocks
    // @param {object} block - Der Block-Objekt
    focus(element, block) {
        if (!element) return false;
        
        // Prüfe ob das Element selbst fokussierbar ist
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
