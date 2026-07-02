export const editorInlineContentMethods = {
    queueInlineContentDraft(key, content, applyUpdate) {
        if (!key || typeof applyUpdate !== 'function') {
            return;
        }

        if (this.updateBlockContentTimeouts.has(key)) {
            clearTimeout(this.updateBlockContentTimeouts.get(key));
            this.updateBlockContentTimeouts.delete(key);
        }

        this.inlineContentBuffer.set(key, { content, applyUpdate });
        this.needsLivewireSync = true;
    },

    queueInlineContentUpdate(key, content, applyUpdate, debounceMs = null) {
        if (!key || typeof applyUpdate !== 'function') {
            return;
        }

        const delay = Number.isFinite(debounceMs) ? debounceMs : this.inlineContentDebounceMs;
        this.inlineContentBuffer.set(key, { content, applyUpdate });

        if (this.updateBlockContentTimeouts.has(key)) {
            clearTimeout(this.updateBlockContentTimeouts.get(key));
        }

        const timeoutId = setTimeout(() => {
            const pending = this.inlineContentBuffer.get(key);
            this.inlineContentBuffer.delete(key);
            this.updateBlockContentTimeouts.delete(key);

            if (pending) {
                try {
                    pending.applyUpdate(pending.content);
                } catch (error) {
                    console.warn('Fehler beim Anwenden des Content-Updates:', error);
                }
                this.invalidateJSONDisplayCache();
            }
        }, delay);

        this.updateBlockContentTimeouts.set(key, timeoutId);
    },

    clearInlineContentUpdate(key) {
        if (!key) return;
        if (this.updateBlockContentTimeouts.has(key)) {
            clearTimeout(this.updateBlockContentTimeouts.get(key));
            this.updateBlockContentTimeouts.delete(key);
        }
        this.inlineContentBuffer.delete(key);
    },

    flushInlineContentUpdates(markDirty = true) {
        if (this.inlineContentBuffer.size === 0) {
            return;
        }

        const pendingEntries = Array.from(this.inlineContentBuffer.entries());
        pendingEntries.forEach(([key, pending]) => {
            if (this.updateBlockContentTimeouts.has(key)) {
                clearTimeout(this.updateBlockContentTimeouts.get(key));
                this.updateBlockContentTimeouts.delete(key);
            }

            if (pending && typeof pending.applyUpdate === 'function') {
                pending.applyUpdate(pending.content);
            }
        });

        this.inlineContentBuffer.clear();
        if (markDirty) {
            this.invalidateJSONDisplayCache();
        }
    },

    updateBlockContent(blockId, content) {
        const key = `block:${blockId}`;
        this.queueInlineContentDraft(key, content, (latestContent) => {
            const { block } = this.findBlockById(blockId);
            if (block) {
                block.content = latestContent;
                block.updatedAt = new Date().toISOString();
            }
        });
    },

    commitBlockContent(blockId, content = null) {
        const { block } = this.findBlockById(blockId);
        if (!block) return;

        const key = `block:${blockId}`;
        const pending = this.inlineContentBuffer.get(key);
        this.clearInlineContentUpdate(key);
        const resolvedContent = (content !== null && content !== undefined)
            ? content
            : (pending ? pending.content : null);

        const currentContent = block.content ?? '';
        const nextContent = (resolvedContent !== null && resolvedContent !== undefined)
            ? resolvedContent
            : currentContent;
        const hasChanged = nextContent !== currentContent;
        if (hasChanged) {
            block.content = nextContent;
        }

        if (!hasChanged) {
            this.invalidateJSONDisplayCache();
            if (block.type === 'code') {
                this.$nextTick(() => {
                    this.highlightCodeBlocks(blockId);
                });
            }
            return;
        }

        block.updatedAt = new Date().toISOString();
        this.invalidateRenderCache(blockId);
        this.invalidateJSONDisplayCache();
        if (block.type === 'code') {
            this.$nextTick(() => {
                this.highlightCodeBlocks(blockId);
            });
        }
    },
};
