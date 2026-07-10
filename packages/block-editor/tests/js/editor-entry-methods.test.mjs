import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { editorEntryMethods } from '../../resources/editor/core/state/editor-entry-methods.js';

describe('editorEntryMethods', () => {
    it('init ruft Bootstrap und Event-Wiring auf', async () => {
        let bootstrap = 0;
        let wired = 0;
        const ctx = {
            runEditorBootstrap() {
                bootstrap += 1;
            },
            setupEditorEventListeners() {
                wired += 1;
            },
        };
        Object.assign(ctx, editorEntryMethods);
        await ctx.init();
        assert.equal(bootstrap, 1);
        assert.equal(wired, 1);
    });
});
