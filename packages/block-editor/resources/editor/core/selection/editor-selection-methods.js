import { getBlockComponent } from '../../components/blocks/index.js';

export const editorSelectionMethods = {
    shouldPreserveCurrentSelection(blockId) {
        if (!blockId) {
            return false;
        }

        const blockElement = this.getBlockElement(blockId);
        if (!blockElement) {
            return false;
        }

        const selectionDetails = this.getActiveSelectionDetails();
        if (!selectionDetails || !selectionDetails.range) {
            return false;
        }

        const { range } = selectionDetails;
        const startNode = range.startContainer?.nodeType === 3
            ? range.startContainer.parentElement
            : range.startContainer;
        const endNode = range.endContainer?.nodeType === 3
            ? range.endContainer.parentElement
            : range.endContainer;

        if (!startNode || !endNode) {
            return false;
        }

        return blockElement.contains(startNode) && blockElement.contains(endNode);
    },

    getActiveSelectionDetails() {
        try {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                return null;
            }

            const selectedText = selection.toString().trim();
            if (!selectedText) {
                return null;
            }

            const range = selection.getRangeAt(0);
            if (!range || range.collapsed) {
                return null;
            }

            return { selection, range, selectedText };
        } catch (_error) {
            return null;
        }
    },

    resolveEditableElementFromRange(range) {
        if (!range) {
            return null;
        }

        let editableElement = range.commonAncestorContainer;
        if (editableElement?.nodeType === 3) {
            editableElement = editableElement.parentElement;
        }

        while (editableElement && editableElement.nodeType === 1) {
            if (editableElement.hasAttribute('contenteditable') && editableElement.getAttribute('contenteditable') === 'true') {
                return editableElement;
            }
            editableElement = editableElement.parentElement;
        }

        return null;
    },

    /**
     * Setzt den Fokus auf ein Block-Element
     * Verwendet die focus-Methode der Block-Komponente, falls vorhanden
     * Nur wenn focusable: true ist, wird der Fokus gesetzt
     * @param {string} blockId - Die Block-ID
     */
    focusBlockElement(blockId) {
        if (!blockId) {
            return;
        }

        const element = this.getBlockElement(blockId);
        if (!element) {
            return;
        }

        // Hole Block-Objekt und Komponente
        const { block } = this.findBlockById(blockId);
        if (!block) {
            return;
        }

        const component = getBlockComponent(block.type);

        // Prüfe ob die Komponente fokussierbar ist
        if (component && component.focusable === false) {
            // Komponente ist nicht fokussierbar - kein Fokus setzen
            return;
        }

        // Prüfe ob die Komponente eine focus-Methode hat
        if (component && typeof component.focus === 'function') {
            // Verwende die Komponenten-spezifische focus-Methode
            const result = component.focus(element, block);
            if (result) {
                return; // Komponente hat den Fokus erfolgreich gesetzt
            }
        }

        // Fallback: Standard-Fokus-Verhalten nur für fokussierbare Blöcke
        // Prüfe ob das Element selbst fokussierbar ist (contenteditable)
        if (element.hasAttribute('contenteditable') && element.getAttribute('contenteditable') === 'true') {
            element.focus();
            try {
                const range = document.createRange();
                const sel = window.getSelection();
                range.selectNodeContents(element);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (error) {
                console.warn('Fehler beim Setzen des Cursors:', error);
            }
            return;
        }

        // Für nicht-contenteditable Blöcke: Suche nach fokussierbarem Element innerhalb
        const focusableElement = element.querySelector('[contenteditable="true"]');
        if (focusableElement) {
            focusableElement.focus();
            try {
                const range = document.createRange();
                const sel = window.getSelection();
                range.selectNodeContents(focusableElement);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (error) {
                console.warn('Fehler beim Setzen des Cursors:', error);
            }
        }
    },

    deselectAll() {
        this.selectedBlockId = null;
        this.mediaClickArmedBlockId = null;
        this.closeBlockToolbar();
    }
};
