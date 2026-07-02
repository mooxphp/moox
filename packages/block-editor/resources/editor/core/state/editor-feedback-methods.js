import { hideNotificationState, showNotificationState } from '../notifications/state.js';
import { getDefaultConfirmModalState as buildDefaultConfirmModalState } from './ui-defaults.js';

export const editorFeedbackMethods = {
    closeConfirmModal() {
        this.showConfirmModal = false;
        this.confirmModal = this.getDefaultConfirmModalState();
        if (window.modalHelpers) window.modalHelpers.closeModal();
    },

    getDefaultConfirmModalState() {
        return buildDefaultConfirmModalState();
    },

    openConfirmModal(config = {}) {
        this.confirmModal = {
            ...this.getDefaultConfirmModalState(),
            ...config,
        };
        this.showConfirmModal = true;
        if (window.modalHelpers) {
            window.modalHelpers.openModal();
        }
    },

    confirmAction() {
        if (this.confirmModal.onConfirm) {
            this.confirmModal.onConfirm();
        }
    },

    /**
     * Zeigt eine Notification-Nachricht an
     * @param {string} message - Die Nachricht (kann auch HTML enthalten)
     * @param {string} type - Der Typ: 'success', 'error', 'info', 'warning'
     * @param {number} duration - Dauer in Millisekunden bis zum automatischen Ausblenden (Standard: 3000, 0 = kein automatisches Ausblenden)
     */
    showNotification(message, type = 'success', duration = 3000) {
        showNotificationState({
            notification: this.notification,
            message,
            type,
            duration,
            notificationTimeout: this.notificationTimeout,
            setNotificationTimeout: (value) => {
                this.notificationTimeout = value;
            },
            logger: console,
        });
    },

    hideNotification() {
        hideNotificationState({
            notification: this.notification,
            notificationTimeout: this.notificationTimeout,
            setNotificationTimeout: (value) => {
                this.notificationTimeout = value;
            },
        });
    },

    setCalloutVariant(blockId, variant) {
        const { block } = this.findBlockById(blockId);
        if (!block || block.type !== 'callout') {
            return;
        }

        const normalizedVariant = ['info', 'warning', 'success', 'danger'].includes(variant) ? variant : 'info';
        block.calloutVariant = normalizedVariant;
        block.updatedAt = new Date().toISOString();
        this.invalidateRenderCache(blockId);
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateJSONDisplayCache();
    },
};
