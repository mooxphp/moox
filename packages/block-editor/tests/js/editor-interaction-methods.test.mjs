import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { editorInteractionMethods } from '../../resources/editor/core/state/editor-interaction-methods.js';
import {
    buildMediaLibraryUrl,
    buildMediaLibraryCacheKey,
    buildMediaUploadUrl,
    normalizeMediaLibraryItems
} from '../../resources/editor/core/media/editor-media-methods.js';
import {
    getDefaultImageSettingsState,
    getDefaultVideoSettingsState
} from '../../resources/editor/core/media/modal-state.js';

describe('editorInteractionMethods', () => {
    it('findBlockById nutzt den Lookup-Cache pro Version', () => {
        const blocks = [{ id: '1', type: 'paragraph', content: '' }];
        const ctx = {
            blocks,
            blockLookupCache: new Map(),
            blockLookupCacheVersion: 0,
        };
        Object.assign(ctx, editorInteractionMethods);
        const first = ctx.findBlockById('1');
        const second = ctx.findBlockById('1');
        assert.equal(first.block?.id, '1');
        assert.strictEqual(first, second);
        ctx.invalidateBlockLookupCache();
        const third = ctx.findBlockById('1');
        assert.equal(third.block?.id, '1');
        assert.notStrictEqual(first, third);
    });

    it('getAllBlocks delegiert an Utils', () => {
        const blocks = [{ id: 'a' }];
        const ctx = { blocks };
        Object.assign(ctx, editorInteractionMethods);
        const all = ctx.getAllBlocks();
        assert.ok(Array.isArray(all));
        assert.ok(all.length >= 1);
    });
});

describe('editorMediaMethods helper', () => {
    it('baut die Mediathek-URL mit Query-Parametern', () => {
        const url = buildMediaLibraryUrl('/api/media', 'test', '1', 'Image', 2, 12);
        assert.equal(url, 'http://localhost/api/media?search=test&collection=1&type=image&page=2&per_page=12');
    });

    it('lässt collection weg, wenn nicht gesetzt', () => {
        const url = buildMediaLibraryUrl('/api/media', 'test', null, 'video', 1, 25);
        assert.equal(url, 'http://localhost/api/media?search=test&type=video&page=1&per_page=25');
    });

    it('lässt collection weg, wenn collection keine ID ist', () => {
        const url = buildMediaLibraryUrl('/api/media', 'test', 'Default', 'image', 1, 25);
        assert.equal(url, 'http://localhost/api/media?search=test&type=image&page=1&per_page=25');
    });

    it('baut die Upload-URL ohne Query-Parameter', () => {
        const url = buildMediaUploadUrl('/api/media');
        assert.equal(url, 'http://localhost/api/media');
    });

    it('erstellt stabilen Cache-Key aus Request-Parametern', () => {
        const cacheKey = buildMediaLibraryCacheKey('/api/media', 'hero', '1', 'image', 2, 25);
        assert.equal(cacheKey, 'http://localhost/api/media?search=hero&collection=1&type=image&page=2&per_page=25');
    });

    it('normalisiert unterschiedliche API-Responses zu Media-Items', () => {
        const items = normalizeMediaLibraryItems({
            data: [
                {
                    id: 5,
                    original_url: 'https://cdn.test/image.jpg',
                    name: 'Hero Bild',
                    thumbnail_url: 'https://cdn.test/image-thumb.jpg',
                    collection: { id: 3, name: 'Default' }
                }
            ]
        }, 'Image');

        assert.equal(items.length, 1);
        assert.equal(items[0].id, '5');
        assert.equal(items[0].url, 'https://cdn.test/image.jpg');
        assert.equal(items[0].originalUrl, 'https://cdn.test/image.jpg');
        assert.equal(items[0].previewUrl, 'https://cdn.test/image-thumb.jpg');
        assert.equal(items[0].title, 'Hero Bild');
        assert.equal(items[0].type, 'image');
        assert.equal(items[0].collectionId, '3');
    });

    it('normalisiert Upload-Response mit data-Objekt', () => {
        const items = normalizeMediaLibraryItems({
            data: {
                id: 11,
                url: 'https://cdn.test/uploaded.jpg',
                thumbnail_url: 'https://cdn.test/uploaded-thumb.jpg',
                title: 'Neu hochgeladen',
                type: 'image'
            }
        }, 'image');

        assert.equal(items.length, 1);
        assert.equal(items[0].id, '11');
        assert.equal(items[0].url, 'https://cdn.test/uploaded.jpg');
        assert.equal(items[0].originalUrl, 'https://cdn.test/uploaded.jpg');
        assert.equal(items[0].previewUrl, 'https://cdn.test/uploaded.jpg');
        assert.equal(items[0].title, 'Neu hochgeladen');
        assert.equal(items[0].type, 'image');
    });

    it('bevorzugt url gegenüber preview_url als Asset-URL', () => {
        const items = normalizeMediaLibraryItems({
            data: [
                {
                    id: 9,
                    url: 'https://cdn.test/original.jpg',
                    preview_url: 'https://cdn.test/preview.jpg',
                    type: 'image'
                }
            ]
        }, 'image');

        assert.equal(items.length, 1);
        assert.equal(items[0].url, 'https://cdn.test/original.jpg');
        assert.equal(items[0].originalUrl, 'https://cdn.test/original.jpg');
        assert.equal(items[0].previewUrl, 'https://cdn.test/original.jpg');
    });

    it('öffnet Bild/Video standardmäßig im Mediathek-Tab', () => {
        assert.equal(getDefaultImageSettingsState().imageSettingsActiveTab, 'library');
        assert.equal(getDefaultVideoSettingsState().videoSettingsActiveTab, 'library');
    });
});
