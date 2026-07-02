export const editorElementCacheMethods = {
    // Optimierte Helper-Funktion für DOM-Element-Abfragen mit Caching
    getBlockElement(blockId) {
        if (!blockId) return null;

        // Prüfe Cache
        if (this.elementCache.has(blockId)) {
            const cached = this.elementCache.get(blockId);
            // Prüfe ob Element noch im DOM ist
            if (document.contains(cached)) {
                return cached;
            } else {
                // Element nicht mehr im DOM, entferne aus Cache
                this.elementCache.delete(blockId);
            }
        }

        // Element nicht im Cache, suche im DOM
        const element = document.querySelector(`[data-block-id="${blockId}"]`);
        if (element) {
            this.elementCache.set(blockId, element);
        }
        return element;
    },

    // Cache invalidieren für einen Block
    invalidateBlockCache(blockId) {
        if (blockId) {
            this.elementCache.delete(blockId);
        }
    },

    // Cache komplett leeren (z.B. nach größeren DOM-Änderungen)
    clearElementCache() {
        this.elementCache.clear();
    },
};
