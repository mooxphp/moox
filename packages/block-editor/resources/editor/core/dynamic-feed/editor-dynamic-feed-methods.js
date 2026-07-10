import * as EditorConfig from '../config/editor-config.js';

const DEFAULT_LIMIT = 5;
const DEFAULT_ORDER_BY = 'published_at';
const DEFAULT_ORDER_DIRECTION = 'desc';

function normalizeLimit(value) {
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) {
        return DEFAULT_LIMIT;
    }

    return Math.max(1, Math.min(50, Math.round(parsed)));
}

function resolveDynamicFeedHost(rootElement) {
    if (!rootElement) {
        return null;
    }

    if (typeof rootElement.closest === 'function') {
        const host = rootElement.closest('[data-editor-instance]');
        if (host) {
            return host;
        }
    }

    return rootElement.parentElement ?? rootElement;
}

export const editorDynamicFeedMethods = {
    dynamicFeedSources: [],

    initializeDynamicFeedSources() {
        const host = resolveDynamicFeedHost(this.$root);
        this.dynamicFeedSources = EditorConfig.resolveDynamicFeedSources(host);

        if (this.dynamicFeedSources.length === 0 && this.$root) {
            this.dynamicFeedSources = EditorConfig.resolveDynamicFeedSources(this.$root);
        }
    },

    resolveDynamicFeedBlock(blockId) {
        const { block } = this.findBlockById(blockId);

        if (!block || block.type !== 'dynamicFeed') {
            return null;
        }

        return block;
    },

    markDynamicFeedBlockChanged(blockId) {
        this.invalidateBlockSettingsCache(blockId);
        this.invalidateRenderCache(blockId);
        this.invalidateBlockLookupCache?.();
        this.blockSettingsVersion++;
    },

    getDynamicFeedSourceMeta(block) {
        if (!block?.sourceKey) {
            return null;
        }

        return this.dynamicFeedSources.find((source) => source.key === block.sourceKey) ?? null;
    },

    getDynamicFeedFilterSchema(block) {
        const source = this.getDynamicFeedSourceMeta(block);
        const schema = source?.filterSchema;

        if (!schema || typeof schema !== 'object') {
            return [];
        }

        if (Array.isArray(schema)) {
            return schema;
        }

        return Object.entries(schema).map(([key, value]) => ({
            key,
            ...(value && typeof value === 'object' ? value : {}),
        }));
    },

    getDynamicFeedViews(block) {
        const source = this.getDynamicFeedSourceMeta(block);
        return Array.isArray(source?.views) ? source.views : [];
    },

    getDynamicFeedFilterOptions(sourceKey, filterKey) {
        const source = this.dynamicFeedSources.find((entry) => entry.key === sourceKey);
        const options = source?.filterOptions?.[filterKey];

        return Array.isArray(options) ? options : [];
    },

    isDynamicFeedFilterOptionsLoading() {
        return false;
    },

    ensureDynamicFeedBlockReady(block) {
        if (!block || block.type !== 'dynamicFeed') {
            return;
        }

        if (this.dynamicFeedSources.length === 0) {
            this.initializeDynamicFeedSources();
        }

        if (!block.sourceKey && this.dynamicFeedSources.length === 1) {
            this.updateDynamicFeedSource(block.id, this.dynamicFeedSources[0].key);
        }
    },

    updateDynamicFeedSource(blockId, sourceKey) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block) {
            return;
        }

        block.sourceKey = sourceKey || '';
        block.filters = {};
        block.view = '';
        block.limit = normalizeLimit(block.limit);
        block.orderBy = block.orderBy || DEFAULT_ORDER_BY;
        block.orderDirection = block.orderDirection || DEFAULT_ORDER_DIRECTION;
        block.updatedAt = new Date().toISOString();

        const source = this.getDynamicFeedSourceMeta(block);
        if (source?.defaultView) {
            block.view = source.defaultView;
        }

        this.markDynamicFeedBlockChanged(blockId);
        this.syncLivewireState?.(true);

        this.$nextTick(() => {
            this.ensureDynamicFeedBlockReady(block);
        });
    },

    updateDynamicFeedFilter(blockId, filterKey, value) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block) {
            return;
        }

        if (!block.filters || typeof block.filters !== 'object') {
            block.filters = {};
        }

        if (value === '' || value === null || value === undefined) {
            delete block.filters[filterKey];
        } else {
            block.filters[filterKey] = value;
        }

        block.updatedAt = new Date().toISOString();
        this.markDynamicFeedBlockChanged(blockId);
        this.syncLivewireState?.(true);
    },

    updateDynamicFeedLimit(blockId, value) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block) {
            return;
        }

        block.limit = normalizeLimit(value);
        block.updatedAt = new Date().toISOString();
        this.markDynamicFeedBlockChanged(blockId);
        this.syncLivewireState?.(true);
    },

    updateDynamicFeedView(blockId, view) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block) {
            return;
        }

        block.view = view || '';
        block.updatedAt = new Date().toISOString();
        this.markDynamicFeedBlockChanged(blockId);
        this.syncLivewireState?.(true);
    },
};
