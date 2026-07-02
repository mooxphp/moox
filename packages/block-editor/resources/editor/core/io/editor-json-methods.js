import { BlockManagement } from '../blocks/management.js';
import { validateImportJsonText } from './import-validation.js';
import { Storage } from './storage.js';
import { Utils } from '../utils/index.js';

export const editorJsonMethods = {
    saveToJSON() {
        this.flushInlineContentUpdates();
        try {
            const saveResult = Storage.saveToJSON(this.blocks);

            if (!saveResult?.persisted) {
                this.showNotification('Speichern fehlgeschlagen. Bitte erneut versuchen.', 'error');
                return;
            }

            // Erfolgs-Notification bewusst unterdrückt, um irreführende Meldungen zu vermeiden.
        } catch (_error) {
            this.showNotification('Speichern fehlgeschlagen. Bitte erneut versuchen.', 'error');
        }
    },

    importJSON() {
        this.showImportModal = true;
        this.importJSONText = '';
        this.importJSONValid = false;
        this.importJSONError = null;
        this.importJSONPreview = null;
        if (window.modalHelpers) {
            window.modalHelpers.openModal();
        }
    },

    closeImportModal() {
        this.showImportModal = false;
        this.importJSONText = '';
        this.importJSONValid = false;
        this.importJSONError = null;
        this.importJSONPreview = null;
        if (window.modalHelpers) {
            window.modalHelpers.closeModal();
        }
    },

    validateImportJSON() {
        // Debounce für bessere Performance bei schnellen Eingaben
        if (this.validateJSONTimeout) {
            clearTimeout(this.validateJSONTimeout);
        }

        this.validateJSONTimeout = setTimeout(() => {
            this.importJSONError = null;
            this.importJSONValid = false;
            this.importJSONPreview = null;

            const validationResult = validateImportJsonText(this.importJSONText);
            this.importJSONValid = validationResult.valid;
            this.importJSONError = validationResult.error;
            this.importJSONPreview = validationResult.preview;

            this.validateJSONTimeout = null;
        }, 300); // 300ms Debounce für JSON-Validierung
    },

    confirmImportJSON() {
        if (!this.importJSONValid || !this.importJSONText) {
            return;
        }

        const updateCounter = (newCounter) => {
            this.blockIdCounter = newCounter;
        };

        try {
            Storage.importJSON(
                this.importJSONText,
                this.blocks,
                this.blockIdCounter,
                this.$nextTick.bind(this),
                Utils.initAllBlockContents,
                updateCounter
            );

            const blockCount = this.importJSONPreview?.blockCount || 0;
            // Cache komplett leeren nach JSON-Import (DOM wurde komplett neu aufgebaut)
            this.clearElementCache();
            this.invalidateRenderCache(); // Invalidiere gesamten Render-Cache
            this.invalidateJSONDisplayCache();
            this.showNotification(`Erfolgreich ${blockCount} Block(s) importiert!`, 'success');
            this.closeImportModal();
        } catch (error) {
            this.importJSONError = error.message || 'Fehler beim Importieren der Daten.';
            this.showNotification('Fehler beim Importieren: ' + error.message, 'error');
        }
    },

    async copyJSONToClipboard() {
        try {
            this.flushInlineContentUpdates();
            // Stelle sicher, dass die Struktur korrekt ist
            BlockManagement.ensureColumnStructure(this.blocks);
            const json = JSON.stringify(this.blocks, null, 2);

            // Moderne Clipboard API verwenden falls verfügbar
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(json);
                this.showNotification('wurde in Zwischenablage kopiert', 'success');
            } else {
                // Fallback für ältere Browser
                const textarea = document.createElement('textarea');
                textarea.value = json;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                textarea.setSelectionRange(0, 99999);
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.showNotification('wurde in Zwischenablage kopiert', 'success');
            }
        } catch (err) {
            console.error('Fehler beim Kopieren:', err);
            this.showNotification('Fehler beim Kopieren in die Zwischenablage.', 'error');
        }
    }
};
