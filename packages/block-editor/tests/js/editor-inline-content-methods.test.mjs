import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { editorInlineContentMethods } from '../../resources/editor/core/state/editor-inline-content-methods.js';

describe('editorInlineContentMethods', () => {
    it('updateBlockContent puffert Drafts und rendert erst beim Commit', async () => {
        const blocks = [
            { id: '1', type: 'paragraph', content: 'a' },
            { id: '2', type: 'code', content: 'x' },
        ];
        const ctx = {
            inlineContentDebounceMs: 5,
            inlineContentBuffer: new Map(),
            updateBlockContentTimeouts: new Map(),
            jsonInvalidations: 0,
            renderInvalidations: 0,
            highlighted: 0,
            nextTickCalls: 0,
            needsLivewireSync: false,
            invalidateJSONDisplayCache() {
                this.jsonInvalidations += 1;
            },
            invalidateRenderCache() {
                this.renderInvalidations += 1;
            },
            highlightCodeBlocks() {
                this.highlighted += 1;
            },
            $nextTick(fn) {
                this.nextTickCalls += 1;
                fn();
            },
            findBlockById(id) {
                const block = blocks.find((b) => b.id === id);
                return { block };
            },
        };
        Object.assign(ctx, editorInlineContentMethods);
        ctx.updateBlockContent('1', 'b');
        await new Promise((r) => setTimeout(r, 15));
        assert.equal(blocks[0].content, 'a');
        assert.equal(ctx.inlineContentBuffer.get('block:1')?.content, 'b');
        assert.equal(ctx.needsLivewireSync, true);
        assert.equal(ctx.renderInvalidations, 0);

        ctx.commitBlockContent('1');
        assert.equal(blocks[0].content, 'b');
        assert.equal(ctx.renderInvalidations, 1);

        const beforeRender = ctx.renderInvalidations;
        ctx.commitBlockContent('2', 'x');
        assert.ok(ctx.highlighted >= 1);
        assert.equal(ctx.renderInvalidations, beforeRender);

        ctx.commitBlockContent('2', 'y');
        assert.equal(blocks[1].content, 'y');
        assert.ok(ctx.renderInvalidations > beforeRender);
    });

    it('flushInlineContentUpdates wendet ausstehende Updates sofort an', () => {
        const blocks = [{ id: '1', type: 'paragraph', content: 'old' }];
        const ctx = {
            inlineContentDebounceMs: 1000,
            inlineContentBuffer: new Map(),
            updateBlockContentTimeouts: new Map(),
            jsonInvalidations: 0,
            invalidateJSONDisplayCache() {
                this.jsonInvalidations += 1;
            },
        };
        Object.assign(ctx, editorInlineContentMethods);
        ctx.queueInlineContentUpdate('k', 'new', (v) => {
            blocks[0].content = v;
        }, 5000);
        ctx.flushInlineContentUpdates();
        assert.equal(blocks[0].content, 'new');
    });
});
