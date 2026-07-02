import { applyTextAlignmentToSelectionRange } from './alignment.js';
import { getTextAlignmentStateFromSelection } from './alignment-state.js';
import { applyBackgroundColorToSelectionRange } from './background-color.js';
import { getFormatStateFromSelection } from './format-state.js';
import { runPostFormattingUpdate } from './post-formatting.js';
import { computeFloatingToolbarPosition } from './position.js';
import { restoreSelectionAndRemoveFormatting } from './remove-formatting.js';
import { resolveTextSelectionContext } from './selection-context-resolver.js';
import { applyTextFormatToSelectionRange } from './text-format.js';
import { applyTextColorToSelectionRange } from './text-color.js';

export const editorToolbarMethods = {
    handleTextSelection() {
        const context = resolveTextSelectionContext({
            selectionDetails: this.getActiveSelectionDetails(),
            resolveEditableElementFromRange: this.resolveEditableElementFromRange.bind(this),
            findBlockById: this.findBlockById.bind(this),
            getBlockElementByEditable: (editableElement) => editableElement.closest('[data-block-id]')
        });
        if (!context) {
            this.showFloatingToolbar = false;
            return;
        }

        const { rect, snapshot } = context;
        this.setFloatingToolbarPosition({
            top: rect.top,
            left: rect.left + (rect.width / 2),
            selectionRect: rect
        });

        this.selectedText = snapshot.selectedText;
        this.selectedRange = snapshot.selectedRange;
        this.selectedBlockId = snapshot.selectedBlockId;

        // Link-Bearbeitung nur bei expliziter Nutzeraktion (Toolbar-Button oder Link-Klick)
        this.showFloatingToolbar = true;
    },

    setFloatingToolbarPosition({ top = 0, left = 0, selectionRect = null }) {
        const toolbarElement = document.querySelector('.bn-formatting-toolbar');
        this.floatingToolbarPosition = computeFloatingToolbarPosition({
            top,
            left,
            selectionRect,
            toolbarElement
        });
    },

    getFormatState(format) {
        return getFormatStateFromSelection({
            selectedRange: this.selectedRange,
            selectedBlockId: this.selectedBlockId,
            format,
            getBlockElement: this.getBlockElement.bind(this)
        });
    },

    applyTextFormat(format) {
        if (!this.selectedRange || !this.selectedBlockId) {
            return;
        }

        const element = this.getBlockElement(this.selectedBlockId);
        if (!element) {
            this.showFloatingToolbar = false;
            return;
        }

        const formatResult = applyTextFormatToSelectionRange({
            selectedRange: this.selectedRange,
            element,
            format
        });
        if (!formatResult.ok) {
            this.showNotification('Formatierung konnte nicht angewendet werden', 'warning');
            return;
        }
        const selection = formatResult.selection;

        // Aktualisiere Block-/Tabellen-/Checklist-Inhalt
        runPostFormattingUpdate({
            nextTick: this.$nextTick.bind(this),
            updateContentAfterLinkChange: this.updateContentAfterLinkChange.bind(this),
            element,
            selection,
            refreshRange: true,
            onRangeUpdated: (range) => {
                this.selectedRange = range;
            }
        });
    },

    removeFormatting() {
        if (!this.selectedRange || !this.selectedBlockId) {
            return;
        }

        const element = this.getBlockElement(this.selectedBlockId);
        if (!element) {
            this.showFloatingToolbar = false;
            return;
        }

        const removeResult = restoreSelectionAndRemoveFormatting(this.selectedRange);
        if (!removeResult.ok) {
            if (removeResult.reason === 'remove-failed') {
                this.showNotification('Formatierung konnte nicht entfernt werden', 'warning');
            }
            return;
        }

        // Aktualisiere Block-/Tabellen-/Checklist-Inhalt
        runPostFormattingUpdate({
            nextTick: this.$nextTick.bind(this),
            updateContentAfterLinkChange: this.updateContentAfterLinkChange.bind(this),
            element,
            selection: removeResult.selection
        });

        this.showNotification('Formatierung entfernt', 'success');
        this.showFloatingToolbar = false;
        this.selectedRange = null;
    },

    // Rückwärtskompatibilität: Alte Funktionen für Links in Text
    editLink(linkElement, blockElement) {
        this.openLinkModal('edit', { element: linkElement, blockElement });
    },

    updateLink() {
        this.saveLink();
    },

    openLinkInputForSelection() {
        const selection = window.getSelection();
        const selectedText = selection ? selection.toString().trim() : '';

        if (selection && selectedText && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            const container = range.commonAncestorContainer;
            const element = container.nodeType === 3 ? container.parentElement : container;
            const blockElement = element ? element.closest('[data-block-id]') : null;

            if (blockElement) {
                this.selectedBlockId = blockElement.getAttribute('data-block-id');
            }

            this.selectedText = selectedText;
            this.selectedRange = range.cloneRange();
        }

        this.openLinkModal('selection');
    },

    closeLinkInputModal() {
        this.closeLinkModal();
    },

    applyLinkToSelection() {
        this.saveLink();
    },

    applyTextAlignment(blockId, alignment) {
        const { block } = this.findBlockById(blockId);
        if (!block) {
            return;
        }

        // Entferne vorhandene Text-Ausrichtung-Klassen
        const alignmentClasses = ['text-left', 'text-center', 'text-right', 'text-justify'];
        const currentClasses = (block.classes || '').split(' ').filter((cssClass) => !alignmentClasses.includes(cssClass));

        // Füge neue Ausrichtung hinzu
        currentClasses.push(`text-${alignment}`);

        this.updateBlockClasses(blockId, currentClasses.join(' '));
        this.showNotification(`Text-Ausrichtung auf ${alignment === 'left' ? 'links' : alignment === 'center' ? 'zentriert' : alignment === 'right' ? 'rechts' : 'bündig'} gesetzt`, 'success');
    },

    applyTextAlignmentToSelection(alignment) {
        if (!this.selectedRange || !this.selectedBlockId) {
            return;
        }

        const element = this.getBlockElement(this.selectedBlockId);
        if (!element) {
            this.showFloatingToolbar = false;
            return;
        }

        const alignmentResult = applyTextAlignmentToSelectionRange({
            selectedRange: this.selectedRange,
            element,
            alignment
        });

        if (!alignmentResult.ok) {
            this.showNotification('Text-Ausrichtung konnte nicht angewendet werden', 'warning');
            return;
        }

        // Aktualisiere Block-/Tabellen-/Checklist-Inhalt
        runPostFormattingUpdate({
            nextTick: this.$nextTick.bind(this),
            updateContentAfterLinkChange: this.updateContentAfterLinkChange.bind(this),
            element,
            selection: alignmentResult.selection,
            refreshRange: true,
            onRangeUpdated: (range) => {
                this.selectedRange = range;
            }
        });
    },

    getTextAlignmentState(alignment) {
        return getTextAlignmentStateFromSelection({
            alignment,
            selectedRange: this.selectedRange,
            selectedBlockId: this.selectedBlockId,
            getBlockElement: this.getBlockElement.bind(this)
        });
    },

    applyTextColor(color) {
        if (!this.selectedRange || !this.selectedBlockId) {
            return;
        }

        const element = this.getBlockElement(this.selectedBlockId);
        if (!element) {
            this.showFloatingToolbar = false;
            return;
        }

        const colorResult = applyTextColorToSelectionRange(this.selectedRange, color);
        if (!colorResult.ok) {
            this.showNotification('Textfarbe konnte nicht angewendet werden', 'warning');
            return;
        }
        const selection = colorResult.selection;

        // Aktualisiere Block-/Tabellen-/Checklist-Inhalt
        runPostFormattingUpdate({
            nextTick: this.$nextTick.bind(this),
            updateContentAfterLinkChange: this.updateContentAfterLinkChange.bind(this),
            element,
            selection,
            refreshRange: true,
            onRangeUpdated: (range) => {
                this.selectedRange = range;
            }
        });
    },

    applyBackgroundColor(color) {
        if (!this.selectedRange || !this.selectedBlockId) {
            return;
        }

        const element = this.getBlockElement(this.selectedBlockId);
        if (!element) {
            this.showFloatingToolbar = false;
            return;
        }

        const backgroundResult = applyBackgroundColorToSelectionRange(this.selectedRange, color);
        if (!backgroundResult.ok) {
            this.showNotification('Hintergrundfarbe konnte nicht angewendet werden', 'warning');
            return;
        }
        const selection = backgroundResult.selection;

        // Aktualisiere Block-/Tabellen-/Checklist-Inhalt
        runPostFormattingUpdate({
            nextTick: this.$nextTick.bind(this),
            updateContentAfterLinkChange: this.updateContentAfterLinkChange.bind(this),
            element,
            selection,
            refreshRange: true,
            onRangeUpdated: (range) => {
                this.selectedRange = range;
            }
        });
    }
};
