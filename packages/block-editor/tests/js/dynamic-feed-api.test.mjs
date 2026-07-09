import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

describe('dynamic feed api client', () => {
    it('deaktiviert weitere API-Requests nach 401/403', async () => {
        const previousWindow = globalThis.window;
        const previousDocument = globalThis.document;
        const previousFetch = globalThis.fetch;

        let fetchCalls = 0;
        globalThis.window = {};
        globalThis.document = {
            querySelector() {
                return null;
            },
        };
        globalThis.fetch = async () => {
            fetchCalls += 1;
            return {
                ok: false,
                status: 401,
                async json() {
                    return { message: 'Unauthorized' };
                },
            };
        };

        const { fetchDynamicFeedSources } = await import('../../resources/editor/core/io/dynamic-feed-api.js');

        await assert.rejects(() => fetchDynamicFeedSources());
        await assert.rejects(() => fetchDynamicFeedSources());
        assert.equal(fetchCalls, 1, 'Nach erstem 401 darf kein zweiter Netz-Call mehr stattfinden');

        globalThis.window = previousWindow;
        globalThis.document = previousDocument;
        globalThis.fetch = previousFetch;
    });
});
