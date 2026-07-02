import { getBlockComponent } from '../../components/blocks/index.js';

export const editorBlockSettingsMethods = {
    /**
     * Rendert die Einstellungen für einen Block aus der Komponente
     * @param {object} block - Der Block-Objekt
     * @returns {string} HTML-String für die Einstellungen
     */
    renderBlockSettings(block) {
        if (!block || !block.type) {
            return '';
        }

        // Erstelle einen Cache-Key basierend auf Block-ID, Typ und relevanten Daten
        const cacheKey = this.getBlockSettingsCacheKey(block);

        // Prüfe ob Cache vorhanden ist (Version wird in x-effect geprüft)
        const cached = this.blockSettingsCache[cacheKey];
        if (cached) {
            return cached.html;
        }

        const component = getBlockComponent(block.type);
        if (component && component.getSettingsHTML) {
            const settingsHTML = component.getSettingsHTML(block, {
                selectedBlockId: this.selectedBlockId,
                draggingBlockId: this.draggingBlockId,
                childBlockTypes: this.childBlockTypes,
                addComponentsEnabled: this.addComponentsEnabled,
                index: this.blocks.findIndex(b => b.id === block.id)
            });

            // Speichere im Cache mit Version
            this.blockSettingsCache[cacheKey] = {
                html: settingsHTML,
                version: this.blockSettingsVersion
            };
            return settingsHTML;
        }
        return '';
    },

    /**
     * Kompatibilitäts-Wrapper für Sidebar-Template
     * @param {object} block - Der Block-Objekt
     * @returns {string} HTML-String für die Einstellungen
     */
    getBlockSettingsHTML(block) {
        return this.renderBlockSettings(block);
    },

    /**
     * Erstellt einen Cache-Key für Block-Einstellungen
     * @param {object} block - Der Block-Objekt
     * @returns {string} Cache-Key
     */
    getBlockSettingsCacheKey(block) {
        if (!block || !block.id) {
            return '';
        }

        // Erstelle Key basierend auf Block-ID, Typ und relevanten Daten
        let key = `${block.id}_${block.type}`;

        // Für Tabellen: Füge tableData-Info hinzu
        if (block.type === 'table' && block.tableData) {
            const rowCount = block.tableData.cells?.length || 0;
            const colCount = block.tableData.cells?.[0]?.length || 0;
            const hasHeader = block.tableData.hasHeader || false;
            const hasFooter = block.tableData.hasFooter || false;
            key += `_${rowCount}_${colCount}_${hasHeader}_${hasFooter}`;
        }

        // Für Checklist: Füge Items-Count hinzu
        if (block.type === 'checklist' && block.checklistData) {
            const itemsCount = block.checklistData.items?.length || 0;
            key += `_${itemsCount}`;
        }

        // Für List: Füge Items-Count und Style hinzu
        if (block.type === 'list' && block.listData) {
            const itemsCount = block.listData.items?.length || 0;
            const listStyle = block.listData.listStyle || 'unordered';
            key += `_${itemsCount}_${listStyle}`;
        }

        if (block.type === 'tabs' && block.tabsData) {
            const itemsCount = block.tabsData.items?.length || 0;
            const activeTabId = block.tabsData.activeTabId || '';
            key += `_${itemsCount}_${activeTabId}`;
        }

        if (block.type === 'accordion' && block.accordionData) {
            const itemsCount = block.accordionData.items?.length || 0;
            const behavior = block.accordionData.behavior || 'single';
            key += `_${itemsCount}_${behavior}`;
        }

        if (block.type === 'callout') {
            const calloutVariant = block.calloutVariant || 'info';
            key += `_${calloutVariant}`;
        }

        return key;
    },

    /**
     * Invalidiert den Cache für einen Block
     * @param {string} blockId - Die Block-ID
     */
    invalidateBlockSettingsCache(blockId) {
        if (!blockId) {
            // Lösche gesamten Cache
            this.blockSettingsCache = {};
            this.blockSettingsVersion++;
            return;
        }

        // Lösche nur Einträge für diesen Block
        Object.keys(this.blockSettingsCache).forEach(key => {
            if (key.startsWith(`${blockId}_`)) {
                delete this.blockSettingsCache[key];
            }
        });

        // Erhöhe Version für Reaktivität (nur wenn Block gefunden wurde)
        const block = this.blocks.find(b => b.id === blockId) ||
            this.getAllBlocks().find(b => b.id === blockId);
        if (block) {
            this.blockSettingsVersion++;
        }
    },
};
