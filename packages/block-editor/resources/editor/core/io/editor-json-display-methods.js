export const editorJsonDisplayMethods = {
    /**
     * Erstellt einen einfachen Hash für Change-Detection
     * @param {Array} blocks - Die Blöcke
     * @returns {string} Hash-String
     */
    getBlocksHash(blocks) {
        // Change-Detection Hash für den Livewire-Sync.
        // WICHTIG: Der Hash muss auch block-eigene Felder wie `imageUrl` erfassen,
        // sonst wird `wire:model="state"` nicht aktualisiert und Änderungen werden
        // beim Speichern nicht in der DB übernommen.
        if (!blocks || blocks.length === 0) return 'empty';

        const hashSingleBlock = (block) => {
            if (!block || !block.id) {
                return 'invalid_';
            }

            let blockHash = `${block.id}_${block.type}_`;

            if (block.content) {
                blockHash += `${String(block.content).length}_`;
            }

            // Fingerprint all primitive string/number/boolean properties (except structural keys).
            for (const [key, value] of Object.entries(block)) {
                if (
                    key === 'id' ||
                    key === 'type' ||
                    key === 'content' ||
                    key === 'children'
                ) {
                    continue;
                }

                if (typeof value === 'string') {
                    const s = value;
                    const prefix = s.slice(0, 12);
                    const suffix = s.length > 12 ? s.slice(-12) : s;
                    blockHash += `${key}:${s.length}:${prefix}:${suffix}_`;
                } else if (typeof value === 'number') {
                    blockHash += `${key}:${value}_`;
                } else if (typeof value === 'boolean') {
                    blockHash += `${key}:${value ? 1 : 0}_`;
                }
            }

            if (Array.isArray(block.children)) {
                blockHash += `c${block.children.length}_`;
                block.children.forEach((child) => {
                    blockHash += `ch{${hashSingleBlock(child)}}_`;
                });
            }

            return blockHash;
        };

        let hash = `${blocks.length}_`;
        blocks.forEach((block) => {
            hash += hashSingleBlock(block);
        });

        return hash;
    },

    /**
     * Gibt den gecachten JSON-String für das Debug-Display zurück
     * Wird debounced aktualisiert für bessere Performance
     * @returns {string} JSON-String
     */
    getJSONDisplay() {
        // Prüfe ob sich die Blöcke geändert haben
        const currentHash = this.getBlocksHash(this.blocks);

        // Wenn Hash gleich ist und Cache vorhanden, gib Cache zurück
        if (currentHash === this.jsonDisplayHash && this.jsonDisplayCache) {
            return this.jsonDisplayCache;
        }

        // Wenn bereits ein Timeout läuft, gib aktuellen Cache zurück
        if (this.jsonDisplayTimeout) {
            return this.jsonDisplayCache || '{}';
        }

        // Aktualisiere Cache asynchron (debounced)
        this.jsonDisplayTimeout = setTimeout(() => {
            try {
                this.jsonDisplayCache = JSON.stringify(this.blocks, null, 2);
                this.jsonDisplayHash = this.getBlocksHash(this.blocks);
            } catch (_error) {
                this.jsonDisplayCache = '{}';
                this.jsonDisplayHash = '';
            }
            this.jsonDisplayTimeout = null;
        }, 500); // 500ms Debounce für JSON-Display

        // Gib aktuellen Cache zurück (oder leeren String beim ersten Aufruf)
        return this.jsonDisplayCache || '{}';
    },

    /**
     * Invalidiert den JSON-Display-Cache
     */
    invalidateJSONDisplayCache() {
        if (this.jsonDisplayTimeout) {
            clearTimeout(this.jsonDisplayTimeout);
            this.jsonDisplayTimeout = null;
        }
        this.jsonDisplayCache = '';
        this.jsonDisplayHash = '';
    },
};
