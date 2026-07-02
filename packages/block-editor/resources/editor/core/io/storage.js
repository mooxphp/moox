// Storage and Import/Export Functions - als Objekt organisiert
import { BlockManagement } from '../blocks/management.js';
import { renderJSONBlocks } from '../render/json-renderer.js';
import { BlockTypes } from '../../components/block-types.js';
import { apiRequest, fetchTemplatesFromApi, normalizeTemplate } from './templates-api.js';
import { loadThemesFromLocalStorage, saveThemesToLocalStorage } from './theme-local-storage.js';
let generatedIdSequence = 0;

function createGeneratedId(prefix = 'block') {
    generatedIdSequence += 1;

    return `${prefix}-${Date.now()}-${generatedIdSequence}-${Math.random().toString(36).slice(2, 8)}`;
}

function cloneJsonSerializable(value) {
    return JSON.parse(JSON.stringify(value));
}

function requireTrimmedThemeName(themeName, errorMessage) {
    const normalizedName = typeof themeName === 'string' ? themeName.trim() : '';
    if (!normalizedName) {
        throw new Error(errorMessage);
    }

    return normalizedName;
}

function createThemeFilenameBase(name) {
    return name.replace(/[^a-z0-9äöüß_-]/gi, '_').toLowerCase();
}

function createSlug(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9äöüß_-]/gi, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

function hasValidBlockIdentity(block) {
    return Boolean(block && typeof block === 'object' && block.id && block.type);
}

function validateBlockArrayPayload(parsedBlocks) {
    if (!Array.isArray(parsedBlocks)) {
        throw new Error('JSON muss ein Array von Blöcken sein.');
    }

    for (let i = 0; i < parsedBlocks.length; i += 1) {
        if (!hasValidBlockIdentity(parsedBlocks[i])) {
            throw new Error(`Block ${i + 1} fehlt 'id' oder 'type' Feld.`);
        }
    }
}

function assignGeneratedIdsToItems(items, prefix = 'item') {
    if (!Array.isArray(items)) {
        return items;
    }

    return items.map((item) => {
        if (!item || typeof item !== 'object') {
            return item;
        }

        return {
            ...item,
            id: createGeneratedId(prefix),
        };
    });
}

function regenerateBlockIdsRecursive(rawBlocks) {
    if (!Array.isArray(rawBlocks)) {
        return [];
    }

    const blocks = cloneJsonSerializable(rawBlocks);

    const normalizeBlocks = (list) => list
        .map((entry) => {
            if (!entry || typeof entry !== 'object') {
                return null;
            }

            const block = entry;
            block.id = createGeneratedId('block');

            if (BlockTypes.isColumnLikeBlock(block.type)) {
                block.children = Array.isArray(block.children)
                    ? block.children.map((column) => {
                        if (!column || typeof column !== 'object') {
                            return {
                                id: createGeneratedId('col'),
                                type: 'column',
                                children: [],
                            };
                        }

                        const normalizedColumn = {
                            ...column,
                            id: createGeneratedId('col'),
                            type: 'column',
                        };

                        normalizedColumn.children = normalizeBlocks(
                            Array.isArray(normalizedColumn.children) ? normalizedColumn.children : []
                        );

                        return normalizedColumn;
                    })
                    : [];
            } else if (block.type === 'tabs' && block.tabsData && typeof block.tabsData === 'object') {
                const tabsData = block.tabsData;
                const previousActiveId = tabsData.activeTabId ?? null;
                const tabIdMap = new Map();

                tabsData.items = Array.isArray(tabsData.items)
                    ? tabsData.items
                        .map((item) => {
                            if (!item || typeof item !== 'object') {
                                return null;
                            }

                            const originalTabId = item.id ?? null;
                            const newTabId = createGeneratedId('tab');

                            if (originalTabId !== null && originalTabId !== undefined && originalTabId !== '') {
                                tabIdMap.set(String(originalTabId), newTabId);
                            }

                            const normalizedItem = {
                                ...item,
                                id: newTabId,
                            };

                            normalizedItem.children = normalizeBlocks(
                                Array.isArray(normalizedItem.children) ? normalizedItem.children : []
                            );

                            return normalizedItem;
                        })
                        .filter((item) => item !== null)
                    : [];

                if (previousActiveId !== null && previousActiveId !== undefined && tabIdMap.has(String(previousActiveId))) {
                    tabsData.activeTabId = tabIdMap.get(String(previousActiveId));
                } else {
                    tabsData.activeTabId = tabsData.items[0]?.id ?? null;
                }
            } else if (Array.isArray(block.children)) {
                block.children = normalizeBlocks(block.children);
            }

            if (block.tableData?.cells && Array.isArray(block.tableData.cells)) {
                block.tableData.cells = block.tableData.cells.map((row) => {
                    if (!Array.isArray(row)) {
                        return [];
                    }

                    return row.map((cell) => {
                        if (!cell || typeof cell !== 'object') {
                            return cell;
                        }

                        const normalizedCell = {
                            ...cell,
                            id: createGeneratedId('cell'),
                        };

                        if (Array.isArray(normalizedCell.blocks)) {
                            normalizedCell.blocks = normalizeBlocks(normalizedCell.blocks);
                        }

                        return normalizedCell;
                    });
                });
            }

            if (Array.isArray(block.checklistData?.items)) {
                block.checklistData.items = assignGeneratedIdsToItems(block.checklistData.items);
            }

            if (Array.isArray(block.listData?.items)) {
                block.listData.items = assignGeneratedIdsToItems(block.listData.items);
            }

            return block;
        })
        .filter((entry) => entry !== null);

    return normalizeBlocks(blocks);
}

export const Storage = {
    saveToJSON(blocks) {
        // Stelle sicher, dass die Struktur korrekt ist, bevor gespeichert wird
        BlockManagement.ensureColumnStructure(blocks);
        const json = JSON.stringify(blocks, null, 2);
        const previousJson = localStorage.getItem('blockEditorData');
        const hasChanges = previousJson !== json;

        localStorage.setItem('blockEditorData', json);
        const persistedJson = localStorage.getItem('blockEditorData');
        const persisted = persistedJson === json;

        return {
            hasChanges,
            persisted,
        };
    },

    importJSON(jsonText, blocks, blockIdCounter, $nextTick, initAllBlockContents, updateCounter) {
        try {
            const parsedBlocks = JSON.parse(jsonText);

            // Validiere Block-Struktur
            validateBlockArrayPayload(parsedBlocks);

            // Re-generiere IDs rekursiv, um Kollisionen mit bestehenden IDs zu vermeiden.
            const blocksWithFreshIds = regenerateBlockIdsRecursive(parsedBlocks);

            // Rendere alle Blöcke aus dem JSON (zentrale Funktion)
            const renderedBlocks = renderJSONBlocks(blocksWithFreshIds, blockIdCounter);
            
            blocks.splice(0, blocks.length, ...renderedBlocks);
            
            // IDs sind nur noch Timestamps – Counter wird nicht mehr für IDs genutzt
            if (updateCounter) {
                updateCounter(0);
            }
            
            // Initialisiere Block-Inhalte nach dem Rendering
            $nextTick(() => {
                initAllBlockContents(blocks);
            });
            
            // Notification wird vom block-editor.js angezeigt
        } catch (error) {
            // Fehler wird vom block-editor.js angezeigt
            throw error;
        }
    },

    // Theme Management Functions
    async saveTheme(themeName, blocks) {
        const normalizedName = requireTrimmedThemeName(themeName, 'Theme-Name darf nicht leer sein.');
        const slug = createSlug(normalizedName);

        try {
            const payload = await apiRequest('', {
                method: 'POST',
                body: JSON.stringify({
                    name: normalizedName,
                    slug: slug || null,
                    content: cloneJsonSerializable(blocks),
                    meta: {
                        source: 'block-editor'
                    }
                })
            });

            return normalizeTemplate(payload);
        } catch (_error) {
            // Fallback auf lokales Verhalten, falls API nicht erreichbar ist.
        }

        const rawName = normalizedName;
        const sanitizedBase = createThemeFilenameBase(rawName);

        const themes = await this.getAllThemes();
        const nameExists = (name) => themes.some((theme) => theme.name && theme.name.toLowerCase() === name.toLowerCase());
        const filenameExists = (name) => themes.some((theme) => theme.filename === name);

        let uniqueName = rawName;
        let uniqueFilename = `${sanitizedBase}.json`;
        let counter = 1;
        while (nameExists(uniqueName) || filenameExists(uniqueFilename)) {
            counter += 1;
            uniqueName = `${rawName} (${counter})`;
            uniqueFilename = `${sanitizedBase}-${counter}.json`;
        }

        const themeData = {
            name: uniqueName,
            filename: uniqueFilename,
            data: cloneJsonSerializable(blocks),
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };

        themes.push(themeData);
        saveThemesToLocalStorage(themes);

        return themeData;
    },

    async getAllThemes() {
        try {
            const templates = await fetchTemplatesFromApi();
            if (Array.isArray(templates) && templates.length > 0) {
                return templates.map((template) => normalizeTemplate(template));
            }

            // API ist erreichbar, liefert aber keine Daten: nutze lokales Fallback.
        } catch (_error) {
            // Fallback auf bestehende lokale Implementierung.
        }

        return loadThemesFromLocalStorage();
    },

    async loadTheme(themeName, blocks, blockIdCounter, $nextTick, initAllBlockContents, updateCounter) {
        const themes = await this.getAllThemes();
        const theme = themes.find(t => t.name.toLowerCase() === themeName.toLowerCase());
        
        if (!theme) {
            throw new Error(`Theme "${themeName}" nicht gefunden.`);
        }

        const parsedBlocks = (theme.data && Array.isArray(theme.data)) ? theme.data : null;
        
        if (!parsedBlocks) {
            throw new Error(`Theme-Daten für "${themeName}" nicht verfügbar.`);
        }
        
        // Validiere Block-Struktur
        if (!Array.isArray(parsedBlocks)) {
            throw new Error('Theme-Daten sind ungültig.');
        }

        // Rendere alle Blöcke aus dem JSON (zentrale Funktion)
        const renderedBlocks = renderJSONBlocks(parsedBlocks, blockIdCounter);
        
        blocks.splice(0, blocks.length, ...renderedBlocks);
        
        // IDs sind nur noch Timestamps – Counter wird nicht mehr für IDs genutzt
        if (updateCounter) {
            updateCounter(0);
        }
        
        // Initialisiere Block-Inhalte nach dem Rendering
        $nextTick(() => {
            initAllBlockContents(blocks);
        });

        return { blocks: renderedBlocks, blockIdCounter: 0 };
    },

    async deleteTheme(themeName) {
        try {
            const themes = await this.getAllThemes();
            const theme = themes.find((entry) => entry.name.toLowerCase() === themeName.toLowerCase());

            if (theme?.id) {
                await apiRequest(`/${theme.id}`, {
                    method: 'DELETE'
                });

                return true;
            }
        } catch (_error) {
            // Fallback auf lokales Verhalten.
        }

        const themes = await this.getAllThemes();
        const theme = themes.find((entry) => entry.name.toLowerCase() === themeName.toLowerCase());
        if (!theme) {
            return false;
        }

        const filteredThemes = themes.filter((entry) => entry.name.toLowerCase() !== themeName.toLowerCase());
        saveThemesToLocalStorage(filteredThemes);
        
        return true;
    },

    async updateTheme(oldName, newName) {
        const normalizedName = requireTrimmedThemeName(newName, 'Neuer Theme-Name darf nicht leer sein.');
        const slug = createSlug(normalizedName);

        try {
            const themes = await this.getAllThemes();
            const theme = themes.find((entry) => entry.name.toLowerCase() === oldName.toLowerCase());

            if (theme?.id) {
                const payload = await apiRequest(`/${theme.id}`, {
                    method: 'PATCH',
                    body: JSON.stringify({
                        name: normalizedName,
                        slug: slug || null,
                    })
                });

                return normalizeTemplate(payload);
            }
        } catch (_error) {
            // Fallback auf lokales Verhalten.
        }

        const themes = await this.getAllThemes();
        const themeIndex = themes.findIndex((entry) => entry.name.toLowerCase() === oldName.toLowerCase());
        
        if (themeIndex === -1) {
            throw new Error(`Theme "${oldName}" nicht gefunden.`);
        }

        const oldTheme = themes[themeIndex];
        const sanitizedName = createThemeFilenameBase(normalizedName);
        const newFilename = `${sanitizedName}.json`;

        // Prüfe ob neuer Name bereits existiert
        const nameExists = themes.some((entry, index) =>
            index !== themeIndex && (entry.name.toLowerCase() === normalizedName.toLowerCase() || entry.filename === newFilename)
        );
        
        if (nameExists) {
            throw new Error(`Ein Theme mit dem Namen "${normalizedName}" existiert bereits.`);
        }

        // Aktualisiere Theme
        themes[themeIndex] = {
            ...oldTheme,
            name: normalizedName,
            filename: newFilename,
            updatedAt: new Date().toISOString()
        };

        saveThemesToLocalStorage(themes);
        
        return themes[themeIndex];
    },

    async importThemeFromFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const parsedBlocks = JSON.parse(e.target.result);

                    // Validiere Block-Struktur
                    validateBlockArrayPayload(parsedBlocks);

                    // Rendere alle Blöcke aus dem JSON (zentrale Funktion)
                    const renderedBlocks = renderJSONBlocks(parsedBlocks, 0);

                    // Extrahiere Theme-Namen aus Dateinamen
                    const filename = file.name.replace('.json', '');
                    const themeName = filename.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                    const themeData = {
                        name: themeName,
                        filename: file.name,
                        data: renderedBlocks, // Speichere gerenderte Daten im LocalStorage
                        createdAt: new Date().toISOString(),
                        updatedAt: new Date().toISOString()
                    };

                    const slug = createSlug(themeName);

                    apiRequest('', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: themeName,
                            slug: slug || null,
                            content: renderedBlocks,
                            meta: {
                                source: 'import',
                                filename: file.name,
                            }
                        })
                    }).then((apiTheme) => {
                        resolve({ themeData: normalizeTemplate(apiTheme), blocks: renderedBlocks });
                    }).catch(() => {
                        // Fallback auf LocalStorage, falls API nicht erreichbar ist.
                        this.getAllThemes().then((themes) => {
                            const existingIndex = themes.findIndex((entry) => entry.filename === file.name);

                            if (existingIndex !== -1) {
                                themes[existingIndex] = themeData;
                            } else {
                                themes.push(themeData);
                            }

                            saveThemesToLocalStorage(themes);
                            resolve({ themeData, blocks: renderedBlocks });
                        }).catch((error) => {
                            reject(new Error(`Fehler beim Laden der Themes: ${error.message}`));
                        });
                    });
                } catch (error) {
                    reject(new Error(`Fehler beim Parsen der Datei: ${error.message}`));
                }
            };
            
            reader.onerror = () => {
                reject(new Error('Fehler beim Lesen der Datei.'));
            };
            
            reader.readAsText(file);
        });
    }
};

