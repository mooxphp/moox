/**
 * Heading Block Component
 * Enthält alle Informationen für Heading-Blöcke (H1-H6) an einem Ort
 */
import { BLOCK_TYPES } from '../../block-types.js';

const HEADING_LEVEL_CLASSES = {
    1: 'text-3xl',
    2: 'text-2xl',
    3: 'text-xl',
    4: 'text-lg',
    5: 'text-base',
    6: 'text-sm'
};

function focusContentEditable(element) {
    if (!element) return false;
    if (element.getAttribute('contenteditable') !== 'true') return false;
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

function createHeadingBlock(level) {
    const type = `heading${level}`;
    const tag = `h${level}`;
    const sizeClass = HEADING_LEVEL_CLASSES[level];

    return {
        type,
        options: BLOCK_TYPES[type],
        structure: {
            id: '',
            type,
            content: '',
            style: '',
            classes: '',
            htmlId: '',
            createdAt: '',
            updatedAt: ''
        },

        renderHTML(block, context = {}) {
            return this.renderHeadingHTML('block', block, level);
        },

        renderChildHTML(child, context = {}) {
            return this.renderHeadingHTML('child', child, level);
        },

        renderHeadingHTML(scope, data, lvl) {
            const isChild = scope === 'child';
            const placeholder = this.options.placeholder || 'Schreibe eine Überschrift...';
            const addAfterCall = isChild
                ? "addChildAfter((column && column.id) || (block && block.id), childIndex, 'paragraph')"
                : "addBlockAfter(block.id, 'paragraph')";

            return `
            <div x-show="${scope}.type === '${type}'">
                <${tag}
                    :data-block-id="${scope}.id"
                    :id="${scope}.htmlId || null"
                    :style="${scope}.style || ''"
                    :class="['block-placeholder ${sizeClass} font-bold', ${scope}.classes || '']"
                    contenteditable="true"
                    data-placeholder="${placeholder}"
                    x-init="$nextTick(() => initBlockContent($el, ${scope}))"
                    @paste.prevent="handlePlainTextPaste($event)"
                    @input="updateBlockContent(${scope}.id, $event.target.innerHTML)"
                    @blur="commitBlockContent(${scope}.id, $event.target.innerHTML)"
                    @keydown.enter.prevent="${addAfterCall}"
                    @keydown.backspace="handleBackspace(${scope}.id, $event)"
                    @focus="initBlockContent($event.target, ${scope})"
                ></${tag}>
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

        focusable: true,
        focus(element, block) {
            return focusContentEditable(element);
        }
    };
}

export const Heading1Block = createHeadingBlock(1);
export const Heading2Block = createHeadingBlock(2);
export const Heading3Block = createHeadingBlock(3);
export const Heading4Block = createHeadingBlock(4);
export const Heading5Block = createHeadingBlock(5);
export const Heading6Block = createHeadingBlock(6);
