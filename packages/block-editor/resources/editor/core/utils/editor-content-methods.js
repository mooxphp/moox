import { Utils } from './index.js';
import { BlockTypes } from '../../components/block-types.js';
import { sanitizeHtmlContent as sanitizeHtmlContentHelper } from './dom.js';

export const editorContentMethods = {
    initAllBlockContents() {
        Utils.initAllBlockContents(this.blocks);
        this.$nextTick(() => {
            this.highlightCodeBlocks();
        });
    },

    highlightCodeBlocks(blockId = null) {
        const codeElements = [];

        if (blockId) {
            const preElement = document.querySelector(`pre[data-block-id="${blockId}"]`);
            if (preElement) {
                const codeElement = preElement.querySelector('code');
                if (codeElement) {
                    codeElements.push(codeElement);
                }
            }
        } else {
            const foundElements = document.querySelectorAll('pre[data-block-id] > code');
            foundElements.forEach((element) => codeElements.push(element));
        }

        codeElements.forEach((codeElement) => {
            this.applyCodeHighlighting(codeElement);
        });
    },

    applyCodeHighlighting(codeElement) {
        if (!codeElement) {
            return;
        }

        // Keine Highlight-Injektion auf editierbaren Elementen:
        // Prism/hljs schreiben HTML-Tags in den Code und zerstören sonst die Bearbeitungsformatierung.
        if (codeElement.getAttribute('contenteditable') === 'true') {
            return;
        }

        // Während des aktiven Tippens kein Highlighting anwenden.
        if (codeElement === document.activeElement) {
            return;
        }

        try {
            if (window.Prism && typeof window.Prism.highlightElement === 'function') {
                window.Prism.highlightElement(codeElement);
                return;
            }

            if (window.hljs && typeof window.hljs.highlightElement === 'function') {
                window.hljs.highlightElement(codeElement);
            }
        } catch (error) {
            console.warn('Fehler beim Syntax-Highlighting:', error);
        }
    },

    initBlockContent(element, block, isTextContent = false) {
        if (!element || !block) return;
        try {
            Utils.initBlockContent(element, block, isTextContent);
        } catch (error) {
            console.warn('Fehler beim Initialisieren des Block-Inhalts:', error);
        }
    },

    getCalloutVariantClasses(variant) {
        if (variant === 'warning') {
            return 'bg-amber-50 border-amber-300 text-amber-900';
        }
        if (variant === 'success') {
            return 'bg-emerald-50 border-emerald-300 text-emerald-900';
        }
        if (variant === 'danger') {
            return 'bg-red-50 border-red-300 text-red-900';
        }
        return 'bg-blue-50 border-blue-200 text-blue-900';
    },

    isColumnLikeBlock(type) {
        return BlockTypes.isColumnLikeBlock(type);
    },

    getCodeEditableText(element) {
        if (!element) {
            return '';
        }

        // innerText erhält Zeilenumbrüche bei contenteditable robuster als textContent
        // (z. B. nach Paste mit <div>/<br> Strukturen).
        let normalizedText = String(element.innerText ?? '');
        normalizedText = normalizedText.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        return normalizedText;
    },

    insertCodeNewLine(blockId, event) {
        if (!event) {
            return;
        }

        // IME-Eingaben nicht stören.
        if (event.isComposing === true) {
            return;
        }

        const target = event.target;
        if (!target) {
            return;
        }

        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        range.deleteContents();
        const newline = document.createTextNode('\n');
        range.insertNode(newline);

        range.setStartAfter(newline);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);

        this.updateBlockContent(blockId, this.getCodeEditableText(target));
    },

    async copyCodeToClipboard(blockId, event = null) {
        const { block } = this.findBlockById(blockId);
        if (!block || block.type !== 'code') {
            return;
        }

        const content = String(block.content || '');
        const buttonElement = event?.currentTarget || null;
        const originalLabel = buttonElement?.textContent || 'Copy';

        try {
            if (navigator?.clipboard?.writeText) {
                await navigator.clipboard.writeText(content);
            } else {
                const fallbackSucceeded = this.copyTextWithExecCommand(content);
                if (!fallbackSucceeded) {
                    throw new Error('Clipboard fallback failed');
                }
            }

            if (buttonElement) {
                buttonElement.textContent = 'Copied';
                setTimeout(() => {
                    buttonElement.textContent = originalLabel;
                }, 1000);
            }
        } catch (error) {
            console.warn('Fehler beim Kopieren des Code-Blocks:', error);
            if (buttonElement) {
                buttonElement.textContent = 'Failed';
                setTimeout(() => {
                    buttonElement.textContent = originalLabel;
                }, 1200);
            }
        }
    },

    copyTextWithExecCommand(text) {
        try {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'fixed';
            textArea.style.top = '-9999px';
            textArea.style.left = '-9999px';
            textArea.style.opacity = '0';

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, textArea.value.length);

            const success = document.execCommand('copy');
            document.body.removeChild(textArea);

            return success === true;
        } catch (error) {
            console.warn('Clipboard execCommand fallback fehlgeschlagen:', error);
            return false;
        }
    },

    sanitizeHtmlContent(value) {
        return sanitizeHtmlContentHelper(value);
    },

    initTableCellContent(element, cell) {
        // Prüfe ob Element und Cell existieren
        if (!element || !cell) return;

        // Nur initialisieren wenn Element leer ist und Cell Inhalt hat
        if (!element.textContent && cell.content) {
            try {
                element.innerHTML = sanitizeHtmlContentHelper(cell.content);
                // Cursor ans Ende setzen
                this.$nextTick(() => {
                    try {
                        const range = document.createRange();
                        const sel = window.getSelection();
                        if (sel && element) {
                            range.selectNodeContents(element);
                            range.collapse(false);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                    } catch (_error) {
                        // Ignoriere Fehler beim Setzen des Cursors
                    }
                });
            } catch (error) {
                console.warn('Fehler beim Initialisieren der Tabellenzelle:', error);
            }
        }
    },
};
