export const editorLifecycleMethods = {
    // Cleanup-Funktion für Event Listener (wird von Alpine.js automatisch aufgerufen)
    destroy() {
        // Cleanup Event Listener
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];

        // Cleanup Timeouts
        if (this.textSelectionTimeout) {
            clearTimeout(this.textSelectionTimeout);
            this.textSelectionTimeout = null;
        }
        if (this.notificationTimeout) {
            clearTimeout(this.notificationTimeout);
            this.notificationTimeout = null;
        }
        if (this.updateBlockContentTimeouts.size > 0) {
            this.updateBlockContentTimeouts.forEach(timeoutId => {
                clearTimeout(timeoutId);
            });
            this.updateBlockContentTimeouts.clear();
        }
        this.inlineContentBuffer.clear();
        if (this.validateJSONTimeout) {
            clearTimeout(this.validateJSONTimeout);
            this.validateJSONTimeout = null;
        }
        if (this.jsonDisplayTimeout) {
            clearTimeout(this.jsonDisplayTimeout);
            this.jsonDisplayTimeout = null;
        }

        // Clear Element Cache
        this.elementCache.clear();
        // Clear Render Cache
        this.renderBlockCache.clear();
        this.renderChildCache.clear();
        // Clear Block Lookup Cache
        this.blockLookupCache.clear();
    },
};
