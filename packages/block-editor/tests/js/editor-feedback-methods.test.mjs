import assert from 'node:assert/strict';
import { afterEach, describe, it } from 'node:test';
import { editorFeedbackMethods } from '../../resources/editor/core/state/editor-feedback-methods.js';

const originalWindow = globalThis.window;

afterEach(() => {
    globalThis.window = originalWindow;
});

describe('editorFeedbackMethods', () => {
    it('openConfirmModal setzt showConfirmModal und merged Konfiguration', () => {
        globalThis.window = {
            modalHelpers: {
                openModal() {},
                closeModal() {},
            },
        };
        const ctx = {
            showConfirmModal: false,
            confirmModal: {},
        };
        Object.assign(ctx, editorFeedbackMethods);
        ctx.openConfirmModal({ title: 'Titel', message: 'Text' });
        assert.equal(ctx.showConfirmModal, true);
        assert.equal(ctx.confirmModal.title, 'Titel');
        assert.equal(ctx.confirmModal.message, 'Text');
    });

    it('setCalloutVariant normalisiert ungültige Werte zu info', () => {
        const block = { type: 'callout', calloutVariant: 'info', updatedAt: null };
        const ctx = {
            findBlockById() {
                return { block };
            },
            invalidateRenderCache() {},
            invalidateBlockSettingsCache() {},
            invalidateJSONDisplayCache() {},
        };
        Object.assign(ctx, editorFeedbackMethods);
        ctx.setCalloutVariant('b1', 'warning');
        assert.equal(block.calloutVariant, 'warning');
        assert.ok(typeof block.updatedAt === 'string');
        ctx.setCalloutVariant('b1', 'not-a-real-variant');
        assert.equal(block.calloutVariant, 'info');
    });

    it('setCalloutVariant ignoriert Nicht-Callout-Blöcke', () => {
        const block = { type: 'paragraph', calloutVariant: undefined };
        const ctx = {
            findBlockById() {
                return { block };
            },
            invalidateRenderCache() {
                throw new Error('should not invalidate');
            },
            invalidateBlockSettingsCache() {},
            invalidateJSONDisplayCache() {},
        };
        Object.assign(ctx, editorFeedbackMethods);
        ctx.setCalloutVariant('b1', 'warning');
        assert.equal(block.calloutVariant, undefined);
    });
});
