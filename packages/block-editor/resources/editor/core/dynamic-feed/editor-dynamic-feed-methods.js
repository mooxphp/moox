import {
    fetchDynamicFeedFilterOptions,
    fetchDynamicFeedPreview,
    fetchDynamicFeedSources,
} from '../io/dynamic-feed-api.js';

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

function buildPreviewCacheKey(block) {
    const filters = block?.filters && typeof block.filters === 'object' ? block.filters : {};

    return JSON.stringify({
        sourceKey: block?.sourceKey ?? '',
        limit: normalizeLimit(block?.limit),
        orderBy: block?.orderBy || DEFAULT_ORDER_BY,
        orderDirection: block?.orderDirection || DEFAULT_ORDER_DIRECTION,
        view: block?.view ?? '',
        filters,
    });
}

export const editorDynamicFeedMethods = {
    dynamicFeedSources: [],
    dynamicFeedSourcesLoading: false,
    dynamicFeedSourcesError: '',
    dynamicFeedFilterOptions: {},
    dynamicFeedFilterOptionsLoading: {},
    dynamicFeedPreviewByBlockId: {},
    dynamicFeedPreviewLoading: {},
    dynamicFeedPreviewError: {},

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

    async ensureDynamicFeedSourcesLoaded() {
        if (this.dynamicFeedSources.length > 0 || this.dynamicFeedSourcesLoading) {
            return;
        }

        this.dynamicFeedSourcesLoading = true;
        this.dynamicFeedSourcesError = '';

        try {
            this.dynamicFeedSources = await fetchDynamicFeedSources();
        } catch (error) {
            this.dynamicFeedSources = [];
            this.dynamicFeedSourcesError = error?.message || 'Quellen konnten nicht geladen werden.';
        } finally {
            this.dynamicFeedSourcesLoading = false;
            this.invalidateBlockSettingsCache(null);
            this.invalidateRenderCache();
            this.blockSettingsVersion++;
        }
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

    getDynamicFeedPreviewState(blockId) {
        return this.dynamicFeedPreviewByBlockId[blockId] ?? { count: 0, items: [], locale: '' };
    },

    isDynamicFeedPreviewLoading(blockId) {
        return Boolean(this.dynamicFeedPreviewLoading[blockId]);
    },

    getDynamicFeedPreviewError(blockId) {
        return this.dynamicFeedPreviewError[blockId] ?? '';
    },

    getDynamicFeedFilterOptionsCacheKey(sourceKey, filterKey) {
        return `${sourceKey}:${filterKey}`;
    },

    getDynamicFeedFilterOptions(sourceKey, filterKey) {
        const cacheKey = this.getDynamicFeedFilterOptionsCacheKey(sourceKey, filterKey);
        return this.dynamicFeedFilterOptions[cacheKey] ?? [];
    },

    isDynamicFeedFilterOptionsLoading(sourceKey, filterKey) {
        const cacheKey = this.getDynamicFeedFilterOptionsCacheKey(sourceKey, filterKey);
        return Boolean(this.dynamicFeedFilterOptionsLoading[cacheKey]);
    },

    async ensureDynamicFeedFilterOptions(sourceKey, filterKey) {
        if (!sourceKey || !filterKey) {
            return;
        }

        const cacheKey = this.getDynamicFeedFilterOptionsCacheKey(sourceKey, filterKey);
        if (Array.isArray(this.dynamicFeedFilterOptions[cacheKey]) || this.dynamicFeedFilterOptionsLoading[cacheKey]) {
            return;
        }

        this.dynamicFeedFilterOptionsLoading[cacheKey] = true;

        try {
            const locale = this.mediaUploadLanguage || '';
            const options = await fetchDynamicFeedFilterOptions(sourceKey, filterKey, locale);
            this.dynamicFeedFilterOptions[cacheKey] = options;
        } catch (_error) {
            this.dynamicFeedFilterOptions[cacheKey] = [];
        } finally {
            this.dynamicFeedFilterOptionsLoading[cacheKey] = false;
            this.invalidateRenderCache();
            this.blockSettingsVersion++;
        }
    },

    async ensureDynamicFeedBlockReady(block) {
        if (!block || block.type !== 'dynamicFeed') {
            return;
        }

        await this.ensureDynamicFeedSourcesLoaded();

        if (!block.sourceKey && this.dynamicFeedSources.length === 1) {
            this.updateDynamicFeedSource(block.id, this.dynamicFeedSources[0].key);
            return;
        }

        if (!block.sourceKey) {
            return;
        }

        const filters = this.getDynamicFeedFilterSchema(block);
        await Promise.all(filters.map((filter) => this.ensureDynamicFeedFilterOptions(block.sourceKey, filter.key)));

        await this.refreshDynamicFeedPreview(block.id);
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

        delete this.dynamicFeedPreviewByBlockId[blockId];
        delete this.dynamicFeedPreviewError[blockId];
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
        this.scheduleDynamicFeedPreviewRefresh(blockId);
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
        this.scheduleDynamicFeedPreviewRefresh(blockId);
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
        this.scheduleDynamicFeedPreviewRefresh(blockId);
    },

    updateDynamicFeedEmptyMessage(blockId, message) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block) {
            return;
        }

        block.emptyMessage = message || '';
        block.updatedAt = new Date().toISOString();
        this.markDynamicFeedBlockChanged(blockId);
        this.syncLivewireState?.(true);
    },

    scheduleDynamicFeedPreviewRefresh(blockId) {
        if (!blockId) {
            return;
        }

        if (!this.dynamicFeedPreviewRefreshTimeouts) {
            this.dynamicFeedPreviewRefreshTimeouts = new Map();
        }

        const existing = this.dynamicFeedPreviewRefreshTimeouts.get(blockId);
        if (existing) {
            clearTimeout(existing);
        }

        const timeout = setTimeout(() => {
            this.dynamicFeedPreviewRefreshTimeouts.delete(blockId);
            this.refreshDynamicFeedPreview(blockId);
        }, 350);

        this.dynamicFeedPreviewRefreshTimeouts.set(blockId, timeout);
    },

    async refreshDynamicFeedPreview(blockId) {
        const block = this.resolveDynamicFeedBlock(blockId);
        if (!block || !block.sourceKey) {
            return;
        }

        const cacheKey = buildPreviewCacheKey(block);
        const cached = this.dynamicFeedPreviewByBlockId[blockId];
        if (cached?.cacheKey === cacheKey) {
            return;
        }

        this.dynamicFeedPreviewLoading[blockId] = true;
        this.dynamicFeedPreviewError[blockId] = '';

        try {
            const preview = await fetchDynamicFeedPreview({
                sourceKey: block.sourceKey,
                limit: block.limit,
                orderBy: block.orderBy,
                orderDirection: block.orderDirection,
                view: block.view,
                filters: block.filters,
                locale: this.mediaUploadLanguage || '',
            });

            this.dynamicFeedPreviewByBlockId[blockId] = {
                ...preview,
                cacheKey,
            };
        } catch (error) {
            this.dynamicFeedPreviewByBlockId[blockId] = {
                count: 0,
                items: [],
                locale: '',
                cacheKey,
            };
            this.dynamicFeedPreviewError[blockId] = error?.message || 'Vorschau konnte nicht geladen werden.';
        } finally {
            this.dynamicFeedPreviewLoading[blockId] = false;
            this.invalidateRenderCache(blockId);
            this.blockSettingsVersion++;
        }
    },
};
