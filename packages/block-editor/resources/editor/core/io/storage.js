// Storage and Import/Export Functions - als Objekt organisiert
import { BlockManagement } from '../blocks/management.js';
import { renderJSONBlocks } from '../render/json-renderer.js';
import { regenerateBlockIdsRecursive } from '../utils/block-ids.js';
import { apiRequest, fetchTemplatesFromApi, normalizeTemplate } from './templates-api.js';
import { loadThemesFromLocalStorage, saveThemesToLocalStorage } from './theme-local-storage.js';

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

        // Immer neue IDs — Theme-JSON darf keine attacker-kontrollierten IDs behalten.
        const blocksWithFreshIds = regenerateBlockIdsRecursive(parsedBlocks);
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

                    // Immer neue IDs — Theme-Datei-Import darf keine attacker-kontrollierten IDs behalten.
                    const blocksWithFreshIds = regenerateBlockIdsRecursive(parsedBlocks);
                    const renderedBlocks = renderJSONBlocks(blocksWithFreshIds, 0);

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

