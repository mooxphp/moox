import { Storage } from '../io/storage.js';
import { renderJSONBlocks } from '../render/json-renderer.js';
import { cloneAndRemapThemeBlocks, getThemeBlocksByName, insertExtendedThemeBlocks } from './extend-theme.js';
import {
    buildDeleteThemeConfirmModal as createDeleteThemeConfirmModal,
    buildImportedThemeConfirmModal as createImportedThemeConfirmModal,
    buildLoadThemeConfirmModal as createLoadThemeConfirmModal
} from '../modals/confirm-builders.js';
import { Utils } from '../utils/index.js';

export const editorThemeMethods = {
    async loadThemes(forceLoad = false) {
        if (!this.themeTemplatesEnabled && !forceLoad) {
            this.themes = [];

            return;
        }
        this.themes = await Storage.getAllThemes();
    },

    openSaveThemeModal() {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.newThemeName = '';
        this.saveThemeError = null;
        this.showSaveThemeModal = true;
        if (window.modalHelpers) {
            window.modalHelpers.openModal();
        }
    },

    closeSaveThemeModal() {
        this.showSaveThemeModal = false;
        this.newThemeName = '';
        this.saveThemeError = null;
        if (window.modalHelpers) {
            window.modalHelpers.closeModal();
        }
    },

    async saveTheme() {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        if (!this.newThemeName || !this.newThemeName.trim()) {
            this.saveThemeError = 'Bitte gib einen Theme-Namen ein.';
            return;
        }

        try {
            const savedTheme = await Storage.saveTheme(this.newThemeName.trim(), this.blocks);
            await this.loadThemes(); // Aktualisiere Themes-Liste
            this.closeSaveThemeModal();

            this.showNotification(`Theme "${savedTheme.name}" erfolgreich gespeichert!`, 'success');
        } catch (error) {
            this.saveThemeError = error.message || 'Fehler beim Speichern des Themes.';
            this.showNotification('Fehler beim Speichern des Themes: ' + error.message, 'error');
        }
    },

    showLoadThemeConfirm(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.openConfirmModal(this.buildLoadThemeConfirmModal(themeName));
    },

    async extendTheme(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.closeConfirmModal();

        try {
            const themes = await Storage.getAllThemes();
            const parsedBlocks = getThemeBlocksByName(themes, themeName);
            const newBlocks = cloneAndRemapThemeBlocks(parsedBlocks);

            // Rendere alle Blöcke aus dem JSON (zentrale Funktion)
            const renderedBlocks = renderJSONBlocks(newBlocks, this.blockIdCounter);

            insertExtendedThemeBlocks(this.blocks, renderedBlocks, this.selectedBlockId, Utils.findBlockById);
            this.blockIdCounter = 0;

            // Initialisiere die neuen Block-Inhalte
            this.$nextTick(() => {
                Utils.initAllBlockContents(renderedBlocks);
            });

            this.showNotification(`Theme "${themeName}" erfolgreich hinzugefügt!`, 'success');
        } catch (error) {
            this.showNotification('Fehler beim Hinzufügen des Themes: ' + error.message, 'error');
        }
    },

    async loadTheme(themeName, forceLoad = false) {
        if (!this.themeTemplatesEnabled && !forceLoad) {
            return;
        }
        this.closeConfirmModal();

        try {
            const updateCounter = (newCounter) => {
                this.blockIdCounter = newCounter;
            };

            await Storage.loadTheme(
                themeName,
                this.blocks,
                this.blockIdCounter,
                this.$nextTick.bind(this),
                Utils.initAllBlockContents,
                updateCounter
            );

            // Cache komplett leeren nach Theme-Laden (DOM wurde komplett neu aufgebaut)
            this.clearElementCache();
            this.selectedBlockId = null;

            this.showNotification(`Theme "${themeName}" erfolgreich geladen!`, 'success');
        } catch (error) {
            this.showNotification('Fehler beim Laden des Themes: ' + error.message, 'error');
        }
    },

    showDeleteThemeConfirm(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.openConfirmModal(this.buildDeleteThemeConfirmModal(themeName));
    },

    async deleteTheme(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.closeConfirmModal();

        try {
            const deleted = await Storage.deleteTheme(themeName);
            if (deleted) {
                await this.loadThemes(); // Aktualisiere Themes-Liste
                this.showNotification(`Theme "${themeName}" erfolgreich gelöscht!`, 'success');
            } else {
                this.showNotification(`Theme "${themeName}" konnte nicht gefunden werden.`, 'error');
            }
        } catch (error) {
            this.showNotification('Fehler beim Löschen des Themes: ' + error.message, 'error');
        }
    },

    openEditThemeModal(themeName) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.editThemeOriginalName = themeName;
        this.editThemeName = themeName;
        this.editThemeError = null;
        this.showEditThemeModal = true;
        if (window.modalHelpers) {
            window.modalHelpers.openModal();
        }
    },

    closeEditThemeModal() {
        this.showEditThemeModal = false;
        this.editThemeName = '';
        this.editThemeOriginalName = '';
        this.editThemeError = null;
        if (window.modalHelpers) {
            window.modalHelpers.closeModal();
        }
    },

    async updateTheme() {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        if (!this.editThemeName || !this.editThemeName.trim()) {
            this.editThemeError = 'Bitte gib einen Theme-Namen ein.';
            return;
        }

        if (this.editThemeName.trim() === this.editThemeOriginalName) {
            this.closeEditThemeModal();
            return;
        }

        try {
            await Storage.updateTheme(this.editThemeOriginalName, this.editThemeName.trim());
            await this.loadThemes(); // Aktualisiere Themes-Liste
            this.closeEditThemeModal();
            this.showNotification(`Theme erfolgreich umbenannt zu "${this.editThemeName.trim()}"!`, 'success');
        } catch (error) {
            this.editThemeError = error.message || 'Fehler beim Umbenennen des Themes.';
            this.showNotification('Fehler beim Bearbeiten des Themes.', 'error');
        }
    },

    openImportThemeModal() {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        this.showImportThemeModal = true;
        if (window.modalHelpers) {
            window.modalHelpers.openModal();
        }
    },

    closeImportThemeModal() {
        this.showImportThemeModal = false;
        if (window.modalHelpers) {
            window.modalHelpers.closeModal();
        }
    },

    async handleThemeFileImport(event) {
        if (!this.themeTemplatesEnabled) {
            return;
        }
        const file = event.target.files[0];
        if (!file) {
            return;
        }

        if (!file.name.endsWith('.json')) {
            this.showNotification('Bitte wählen Sie eine JSON-Datei aus.', 'warning');
            return;
        }

        try {
            const { themeData } = await Storage.importThemeFromFile(file);
            await this.loadThemes(); // Aktualisiere Themes-Liste
            this.closeImportThemeModal();
            this.showNotification(`Theme "${themeData.name}" erfolgreich importiert!`, 'success');

            // Frage ob Theme geladen werden soll
            this.openConfirmModal(this.buildImportedThemeConfirmModal(themeData.name));
        } catch (error) {
            this.showNotification('Fehler beim Importieren des Themes: ' + error.message, 'error');
        }

        // Reset file input
        event.target.value = '';
    },

    buildLoadThemeConfirmModal(themeName) {
        return createLoadThemeConfirmModal(themeName, {
            onConfirm: () => this.loadTheme(themeName),
            onCancel: () => this.closeConfirmModal(),
            onExtend: () => this.extendTheme(themeName)
        });
    },

    buildDeleteThemeConfirmModal(themeName) {
        return createDeleteThemeConfirmModal(themeName, {
            onConfirm: () => this.deleteTheme(themeName),
            onCancel: () => this.closeConfirmModal()
        });
    },

    buildImportedThemeConfirmModal(themeName) {
        return createImportedThemeConfirmModal(themeName, {
            onCancel: () => this.closeConfirmModal(),
            onExtend: () => this.extendTheme(themeName)
        });
    }
};
