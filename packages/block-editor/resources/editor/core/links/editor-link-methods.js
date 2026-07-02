import { getRequiredTrimmedUrl } from '../input/url-input.js';
import {
    buildLinkFollowConfirmModal as createLinkFollowConfirmModal,
} from '../modals/confirm-builders.js';
import { getDefaultLinkModalState as buildDefaultLinkModalState } from '../state/ui-defaults.js';
import {
    applyLinkTarget as applyLinkTargetHelper,
    applyLinkUnderline as applyLinkUnderlineHelper,
    clearLinkMarkersById,
    createLinkFromRange as createLinkFromRangeHelper,
    getLinkElementFromRange as getLinkElementFromRangeHelper,
    getRangeFromMarkers as getRangeFromMarkersHelper,
    getTableCellElementFromNode as getTableCellElementFromNodeHelper,
    getTableCellElementFromSelection as getTableCellElementFromSelectionHelper,
    placeSelectionMarkers as placeSelectionMarkersHelper
} from './dom-helpers.js';
import {
    initializeBlockLinkModalState,
    initializeEditLinkModalState,
    initializeSelectionLinkModalState
} from './modal-initializers.js';
import { applyLinkBlockSettings } from './block-link.js';
import { resolveSelectionRange, restoreSelectionRange } from './selection.js';
import { removeLinkAndSync, syncContentAfterLinkChange, syncEditedLinkContent } from './content-sync.js';

export const editorLinkMethods = {
    /**
     * Öffnet das Link-Modal für verschiedene Kontexte
     * @param {string} type - 'block' (Link-Block), 'selection' (Text-Selektion), 'edit' (Link bearbeiten)
     * @param {object} options - Optionale Parameter je nach Typ
     */
    openLinkModal(type, options = {}) {
        this.linkModal = this.getDefaultLinkModalState(type);

        const canOpen = this.initializeLinkModalByType(type, options);
        if (!canOpen) {
            return;
        }

        this.showLinkModal = true;
        if (window.modalHelpers) window.modalHelpers.openModal();

        this.$nextTick(() => {
            try {
                const root = this.$root?.querySelector('[x-ref="linkUrlField"] input[type="url"]');
                if (root && type === 'block') {
                    root.focus();
                    root.select();
                }
            } catch (e) {
                console.warn('Konnte Link-URL-Feld nicht fokussieren:', e);
            }
        });
    },

    getDefaultLinkModalState(type = null) {
        return buildDefaultLinkModalState(type);
    },

    initializeLinkModalByType(type, options) {
        if (type === 'block') {
            return this.initializeBlockLinkModal(options);
        }

        if (type === 'selection') {
            return this.initializeSelectionLinkModal();
        }

        if (type === 'edit') {
            return this.initializeEditLinkModal(options);
        }

        return true;
    },

    initializeBlockLinkModal(options = {}) {
        const { block } = this.findBlockById(options.blockId);
        return initializeBlockLinkModalState(this.linkModal, options.blockId, block);
    },

    initializeSelectionLinkModal() {
        return initializeSelectionLinkModalState(
            this.linkModal,
            this.selectedRange,
            this.selectedText,
            this.placeSelectionMarkers.bind(this)
        );
    },

    initializeEditLinkModal(options = {}) {
        return initializeEditLinkModalState(this.linkModal, options);
    },

    closeLinkModal() {
        this.showLinkModal = false;
        this.linkModal = this.getDefaultLinkModalState(null);
        this.clearLinkMarkers();
        if (window.modalHelpers) window.modalHelpers.closeModal();
    },

    /**
     * Speichert/aktualisiert den Link je nach Modal-Typ
     */
    saveLink() {
        let rawUrl = (this.linkModal.url || '').trim();
        if (rawUrl !== '' && !rawUrl.startsWith('http://') && !rawUrl.startsWith('https://')) {
            rawUrl = `https://${rawUrl}`;
            this.linkModal.url = rawUrl;
        }

        const urlInput = getRequiredTrimmedUrl(rawUrl);
        if (!urlInput.ok) {
            this.showNotification('Bitte geben Sie eine gültige URL ein (z.B. https://example.com)', 'warning');
            return;
        }

        const url = urlInput.value;
        const target = this.linkModal.target;

        const handlers = {
            block: () => this.saveBlockLink(url, target),
            selection: () => this.saveSelectionLink(url, target),
            edit: () => this.saveEditedLink(url, target)
        };

        const handler = handlers[this.linkModal.type];
        if (!handler) {
            this.showNotification('Unbekannter Link-Typ', 'error');
            return;
        }

        const shouldCloseModal = handler();
        if (shouldCloseModal === false) {
            return;
        }

        this.closeLinkModal();
    },

    saveBlockLink(url, target) {
        const { block } = this.findBlockById(this.linkModal.blockId);
        if (block && block.type === 'link') {
            applyLinkBlockSettings(block, {
                url,
                target,
                linkText: this.linkModal.linkText
            });
        } else {
            this.showNotification('Link-Block nicht gefunden', 'error');
            return false;
        }

        this.showNotification('Link-Einstellungen erfolgreich gespeichert!', 'success');
        return true;
    },

    saveSelectionLink(url, target) {
        const selectionState = resolveSelectionRange(this.linkModal.range);
        const { selection, range, element } = selectionState;
        this.linkModal.range = range;

        if (!range) {
            this.showNotification('Bitte markieren Sie Text für den Link', 'warning');
            return false;
        }

        restoreSelectionRange(selection, range);

        const linkElement = this.getLinkElementFromRange(range);

        if (linkElement) {
            linkElement.setAttribute('href', url);
            applyLinkTargetHelper(linkElement, target);
            this.applyLinkUnderline(linkElement);
        } else {
            try {
                const markerRange = this.getRangeFromMarkers(this.linkModal.markerIds);
                const linkRange = markerRange || range;
                const newLink = this.createLinkFromRange(linkRange, url, target);
                if (newLink) {
                    this.applyLinkUnderline(newLink);
                }
            } catch (error) {
                console.warn('Fehler beim Erstellen des Links:', error);
                this.showNotification('Link konnte nicht erstellt werden', 'warning');
                return false;
            }
        }

        this.updateContentAfterLinkChange(element, selection);
        this.clearLinkMarkers();
        this.showNotification('Link erfolgreich hinzugefügt!', 'success');
        this.showFloatingToolbar = false;
        this.selectedRange = null;
        return true;
    },

    saveEditedLink(url, target) {
        if (!this.linkModal.element) {
            this.showNotification('Link-Element nicht gefunden', 'error');
            return false;
        }

        this.linkModal.element.setAttribute('href', url);
        applyLinkTargetHelper(this.linkModal.element, target);
        this.applyLinkUnderline(this.linkModal.element);

        syncEditedLinkContent({
            linkElement: this.linkModal.element,
            blockId: this.linkModal.blockId,
            ...this.getLinkSyncContext()
        });

        this.showNotification('Link erfolgreich aktualisiert!', 'success');
        return true;
    },

    /**
     * Entfernt einen Link (nur für Edit-Modus)
     */
    removeLink() {
        if (this.linkModal.type !== 'edit' || !this.linkModal.element) return;

        const blockId = this.linkModal.blockId;

        removeLinkAndSync({
            linkElement: this.linkModal.element,
            blockId: blockId,
            ...this.getLinkSyncContext()
        });

        this.showNotification('Link entfernt', 'success');
        this.closeLinkModal();
    },

    /**
     * Helper-Funktion: Aktualisiert Block-Inhalt nach Link-Änderung
     */
    updateContentAfterLinkChange(element, selection) {
        syncContentAfterLinkChange({
            selection: selection,
            selectedBlockId: this.selectedBlockId,
            element: element,
            getTableCellElementFromSelection: this.getTableCellElementFromSelection.bind(this),
            nextTick: this.$nextTick.bind(this),
            updateListItemText: this.updateListItemText.bind(this),
            updateChecklistItemText: this.updateChecklistItemText.bind(this),
            ...this.getLinkSyncContext()
        });
    },

    getLinkSyncContext() {
        return {
            getBlockElement: this.getBlockElement.bind(this),
            nextTick: this.$nextTick.bind(this),
            getTableCellElementFromNode: this.getTableCellElementFromNode.bind(this),
            updateTableCellContent: this.updateTableCellContent.bind(this),
            findBlockById: this.findBlockById.bind(this),
            updateBlockContent: this.updateBlockContent.bind(this)
        };
    },

    /**
     * Stellt sicher, dass Links unterstrichen sind
     */
    applyLinkUnderline(linkElement) {
        applyLinkUnderlineHelper(linkElement);
    },

    placeSelectionMarkers(range) {
        return placeSelectionMarkersHelper(range);
    },

    getRangeFromMarkers(markerIds) {
        return getRangeFromMarkersHelper(markerIds);
    },

    clearLinkMarkers() {
        if (!this.linkModal || !this.linkModal.markerIds || !this.linkModal.markerIds.id) return;
        clearLinkMarkersById(this.linkModal.markerIds.id);
        this.linkModal.markerIds = null;
    },

    getLinkElementFromRange(range) {
        return getLinkElementFromRangeHelper(range);
    },

    createLinkFromRange(range, url, target) {
        return createLinkFromRangeHelper(range, url, target);
    },

    getTableCellElementFromSelection(selection) {
        return getTableCellElementFromSelectionHelper(selection);
    },

    getTableCellElementFromNode(node) {
        return getTableCellElementFromNodeHelper(node);
    },

    getLinkTypeFromUrl(url) {
        const normalizedUrl = String(url || '').trim().toLowerCase();
        if (normalizedUrl === '') {
            return { extension: '', icon: '🔗', label: 'Link' };
        }

        let extension = '';
        const pathnameExtension = normalizedUrl.split('?')[0].split('#')[0].split('.').pop();
        if (pathnameExtension && pathnameExtension !== normalizedUrl) {
            extension = pathnameExtension;
        }

        if (!extension && normalizedUrl.includes('?')) {
            const queryPart = normalizedUrl.split('?')[1]?.split('#')[0] || '';
            const queryMatches = queryPart.match(/(?:file|filename|download)=([^&]+)/);
            if (queryMatches && queryMatches[1]) {
                const decoded = decodeURIComponent(queryMatches[1]);
                const queryExtension = decoded.split('.').pop();
                if (queryExtension && queryExtension !== decoded) {
                    extension = queryExtension.toLowerCase();
                }
            }
        }

        const typeMap = {
            pdf: { icon: '📄', label: 'PDF-Dokument' },
            doc: { icon: '📝', label: 'Word-Dokument' },
            docx: { icon: '📝', label: 'Word-Dokument' },
            odt: { icon: '📝', label: 'Textdokument' },
            xls: { icon: '📊', label: 'Excel-Tabelle' },
            xlsx: { icon: '📊', label: 'Excel-Tabelle' },
            csv: { icon: '📊', label: 'CSV-Datei' },
            ods: { icon: '📊', label: 'Tabellendokument' },
            ppt: { icon: '📽️', label: 'Präsentation' },
            pptx: { icon: '📽️', label: 'Präsentation' },
            odp: { icon: '📽️', label: 'Präsentation' },
            zip: { icon: '🗜️', label: 'Archiv' },
            rar: { icon: '🗜️', label: 'Archiv' },
            '7z': { icon: '🗜️', label: 'Archiv' },
            tar: { icon: '🗜️', label: 'Archiv' },
            gz: { icon: '🗜️', label: 'Archiv' },
            jpg: { icon: '🖼️', label: 'Bild' },
            jpeg: { icon: '🖼️', label: 'Bild' },
            png: { icon: '🖼️', label: 'Bild' },
            gif: { icon: '🖼️', label: 'Bild' },
            webp: { icon: '🖼️', label: 'Bild' },
            svg: { icon: '🖼️', label: 'Vektorgrafik' },
            mp4: { icon: '🎬', label: 'Video' },
            webm: { icon: '🎬', label: 'Video' },
            mov: { icon: '🎬', label: 'Video' },
            mp3: { icon: '🎵', label: 'Audio' },
            wav: { icon: '🎵', label: 'Audio' },
            txt: { icon: '📃', label: 'Textdatei' }
        };

        const entry = typeMap[extension] || { icon: '🔗', label: 'Link' };
        return {
            extension,
            icon: entry.icon,
            label: entry.label
        };
    },

    getLinkTypeIcon(url) {
        return this.getLinkTypeFromUrl(url).icon;
    },

    // Rückwärtskompatibilität: Alte Funktionen als Wrapper
    openLinkSettingsModal(blockId) {
        this.openLinkModal('block', { blockId });
    },

    getLinkFollowData(blockId) {
        const { block } = this.findBlockById(blockId);
        if (!block || block.type !== 'link' || !block.linkUrl) {
            return null;
        }

        const linkUrl = block.linkUrl;
        const linkText = block.linkText || block.content || linkUrl;
        const linkTarget = block.linkTarget || '_blank';

        return {
            linkUrl,
            linkText,
            linkTarget
        };
    },

    buildLinkFollowConfirmModal(blockId, { linkUrl, linkText, linkTarget }) {
        return createLinkFollowConfirmModal(
            { linkUrl, linkText, linkTarget },
            {
                onConfirm: () => {
                    if (linkTarget === '_blank') {
                        window.open(linkUrl, '_blank', 'noopener,noreferrer');
                    } else {
                        window.location.href = linkUrl;
                    }
                    this.closeConfirmModal();
                },
                onCancel: () => {
                    this.closeConfirmModal();
                    this.openLinkSettingsModal(blockId);
                }
            }
        );
    },
};
