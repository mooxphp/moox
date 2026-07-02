import { Utils } from '../utils/index.js';
import { matchBlockShortcut } from '../shortcuts/shortcuts.js';

export const editorInteractionMethods = {
    handleBackspace(blockId, event) {
        const { block, parent } = this.findBlockById(blockId);
        if (block && event.target.textContent === '') {
            if (parent) {
                // Es ist ein Child-Block
                const childIndex = parent.children.findIndex(c => c.id === blockId);
                if (childIndex !== -1 && parent.children.length > 1) {
                    event.preventDefault();
                    this.deleteBlock(blockId);
                }
            } else {
                // Es ist ein Haupt-Block
                if (this.blocks.length > 1) {
                    event.preventDefault();
                    this.deleteBlock(blockId);
                }
            }
        }
    },

    handleQuickListShortcut(blockId, event) {
        if (!event || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        const target = event.target;
        const currentText = target ? (target.textContent || '') : '';
        const match = matchBlockShortcut({ key: event.key, code: event.code, text: currentText, event });
        if (!match) {
            return;
        }

        event.preventDefault();
        this.changeBlockType(blockId, match.action.type);
        if (match.action.type === 'list' && match.action.listStyle) {
            this.setListStyle(blockId, match.action.listStyle);
        }

        this.$nextTick(() => {
            const blockElement = this.getBlockElement(blockId);
            if (!blockElement) return;

            const listItem = blockElement.querySelector('[data-list-item-id]');
            const focusTarget = listItem || blockElement;
            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }

            if (listItem) {
                const range = document.createRange();
                const sel = window.getSelection();
                range.selectNodeContents(listItem);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        });
    },

    findBlockById(blockId) {
        if (!blockId) return { block: null, parent: null };

        // Prüfe Cache
        const cacheKey = `${blockId}_${this.blockLookupCacheVersion}`;
        if (this.blockLookupCache.has(cacheKey)) {
            return this.blockLookupCache.get(cacheKey);
        }

        // Suche Block
        const result = Utils.findBlockById(this.blocks, blockId);

        // Speichere im Cache (nur wenn Block gefunden wurde)
        if (result && result.block) {
            this.blockLookupCache.set(cacheKey, result);

            // Begrenze Cache-Größe (max 100 Einträge)
            if (this.blockLookupCache.size > 100) {
                // Entferne älteste Einträge (einfache Strategie: lösche erste 20)
                const keysToDelete = Array.from(this.blockLookupCache.keys()).slice(0, 20);
                keysToDelete.forEach(key => this.blockLookupCache.delete(key));
            }
        }

        return result;
    },

    /**
     * Invalidiert den Block-Lookup-Cache
     */
    invalidateBlockLookupCache() {
        this.blockLookupCacheVersion++;
        // Optional: Cache komplett leeren wenn zu groß
        if (this.blockLookupCache.size > 50) {
            this.blockLookupCache.clear();
        }
    },

    getAllBlocks() {
        return Utils.getAllBlocks(this.blocks);
    },
};
