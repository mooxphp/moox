import assert from 'node:assert/strict';
import test from 'node:test';
import { normalizeEmbedUrl } from '../../resources/editor/core/utils/embed-url.js';
import { escapeHtmlAttribute, escapeJsSingleQuoted } from '../../resources/editor/core/utils/format.js';
import { isSafeAttributeUrl, safeHrefUrl } from '../../resources/editor/core/utils/dom.js';

test('normalizeEmbedUrl allows youtube and vimeo only', () => {
    assert.equal(normalizeEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ').ok, true);
    assert.equal(normalizeEmbedUrl('https://vimeo.com/123456789').ok, true);
    assert.equal(normalizeEmbedUrl('https://evil.example/embed').ok, false);
    assert.equal(normalizeEmbedUrl('javascript:alert(1)').ok, false);
});

test('escape helpers neutralize attribute and js breakouts', () => {
    assert.equal(escapeHtmlAttribute('"><img src=x onerror=alert(1)>'), '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;');
    assert.equal(escapeJsSingleQuoted("x');alert(1);//"), "x\\');alert(1);//");
    assert.equal(escapeJsSingleQuoted('foo"bar'), 'foo\\x22bar');
});

test('safeHrefUrl blocks javascript urls', () => {
    assert.equal(safeHrefUrl('javascript:alert(1)'), '#');
    assert.equal(safeHrefUrl('https://example.com'), 'https://example.com');
    assert.equal(isSafeAttributeUrl('src', 'data:image/svg+xml,<svg></svg>'), false);
    assert.equal(isSafeAttributeUrl('src', 'data:image/png;base64,abc'), true);
});

test('regenerateBlockIdsRecursive replaces attacker controlled ids', async () => {
    const { regenerateBlockIdsRecursive } = await import('../../resources/editor/core/utils/block-ids.js');
    const input = [
        {
            id: '"><img src=x onerror=alert(1)>',
            type: 'paragraph',
            content: '<p>Hi</p>',
        },
    ];

    const output = regenerateBlockIdsRecursive(input);

    assert.notEqual(output[0].id, input[0].id);
    assert.match(output[0].id, /^block-/);
    assert.equal(output[0].id.includes('"'), false);
});
