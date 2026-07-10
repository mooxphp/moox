/**
 * Dynamic Feed Block Component
 * Konfiguration erfolgt ueber eingebettete Quellen-Metadaten aus dem Backend.
 */
import { BLOCK_TYPES } from '../../block-types.js';

const DEFAULT_LIMIT = 5;
const DEFAULT_ORDER_BY = 'published_at';
const DEFAULT_ORDER_DIRECTION = 'desc';

export const DynamicFeedBlock = {
    type: 'dynamicFeed',
    options: BLOCK_TYPES.dynamicFeed,

    structure: {
        id: '',
        type: 'dynamicFeed',
        sourceKey: '',
        limit: DEFAULT_LIMIT,
        orderBy: DEFAULT_ORDER_BY,
        orderDirection: DEFAULT_ORDER_DIRECTION,
        filters: {},
        view: '',
        style: '',
        classes: '',
        htmlId: '',
        createdAt: '',
        updatedAt: '',
    },

    renderHTML(block, context = {}) {
        return this.renderDynamicFeedHTML('block', block);
    },

    renderChildHTML(child, context = {}) {
        return this.renderDynamicFeedHTML('child', child);
    },

    renderDynamicFeedHTML(scope, data) {
        return `
            <div
                x-show="${scope}.type === 'dynamicFeed'"
                :data-block-id="${scope}.id"
                :id="${scope}.htmlId || null"
                :style="${scope}.style || ''"
                :class="['overflow-hidden rounded-2xl border border-slate-700/80 bg-slate-950 text-slate-100 shadow-[0_18px_40px_rgba(15,23,42,0.35)]', ${scope}.classes || '']"
                x-init="$nextTick(() => ensureDynamicFeedBlockReady(${scope}))"
            >
                <div class="border-b border-slate-800 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-base font-semibold text-white">
                            Dynamic Feed
                        </p>
                        <span
                            x-show="!${scope}.sourceKey"
                            class="mt-3 inline-flex items-center rounded-full border border-amber-400/20 bg-amber-400/10 px-2.5 py-1 text-[11px] font-medium text-amber-200"
                        >
                            Quelle fehlt
                        </span>
                    </div>
                </div>

                <div class="p-5">
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                        <div class="space-y-4">
                            <div>
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Quelle
                                    </label>
                                    <div
                                        x-show="dynamicFeedSources.length === 0"
                                        class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100"
                                    >
                                        Keine Quellen verfuegbar.
                                    </div>
                                    <select
                                        x-show="dynamicFeedSources.length > 0"
                                        :value="${scope}.sourceKey || ''"
                                        @click.stop
                                        @change="updateDynamicFeedSource(${scope}.id, $event.target.value)"
                                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-medium text-white outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30"
                                    >
                                        <option value="">Quelle waehlen…</option>
                                        <template x-for="source in dynamicFeedSources" :key="source.key">
                                            <option :value="source.key" :selected="${scope}.sourceKey === source.key" x-text="source.label"></option>
                                        </template>
                                    </select>
                            </div>

                            <template x-for="filter in getDynamicFeedFilterSchema(${scope})" :key="filter.key">
                                <div>
                                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400" x-text="filter.label || filter.key"></label>
                                        <select
                                            @click.stop
                                            :value="(${scope}.filters && ${scope}.filters[filter.key] !== undefined) ? ${scope}.filters[filter.key] : ''"
                                            @change="updateDynamicFeedFilter(${scope}.id, filter.key, $event.target.value)"
                                            class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-medium text-white outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30"
                                        >
                                            <option value="" x-text="filter.nullable === false ? 'Bitte waehlen…' : 'Alle'"></option>
                                            <template x-for="option in getDynamicFeedFilterOptions(${scope}.sourceKey, filter.key)" :key="option.value">
                                                <option :value="option.value" :selected="${scope}.filters && String(${scope}.filters[filter.key]) === String(option.value)" x-text="option.label"></option>
                                            </template>
                                        </select>
                                </div>
                            </template>

                            <div>
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Anzahl
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="50"
                                        @click.stop
                                        :value="${scope}.limit || ${DEFAULT_LIMIT}"
                                        @input="updateDynamicFeedLimit(${scope}.id, $event.target.value)"
                                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-medium text-white outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30"
                                    />
                            </div>

                            <div>
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Darstellung
                                    </label>
                                    <select
                                        @click.stop
                                        :value="${scope}.view || ''"
                                        @change="updateDynamicFeedView(${scope}.id, $event.target.value)"
                                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-medium text-white outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30"
                                    >
                                        <option value="">Standard</option>
                                        <template x-for="view in getDynamicFeedViews(${scope})" :key="view.key">
                                            <option :value="view.key" :selected="${scope}.view === view.key" x-text="view.label"></option>
                                        </template>
                                    </select>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        `;
    },

    getSettingsHTML(block) {
        return `
            <div x-init="$nextTick(() => ensureDynamicFeedBlockReady(block))"></div>
        `;
    },

    initialize(block) {
        if (!block.sourceKey) block.sourceKey = '';
        if (!block.limit) block.limit = DEFAULT_LIMIT;
        if (!block.orderBy) block.orderBy = DEFAULT_ORDER_BY;
        if (!block.orderDirection) block.orderDirection = DEFAULT_ORDER_DIRECTION;
        if (!block.filters || typeof block.filters !== 'object') block.filters = {};
        if (!block.view) block.view = '';
        return block;
    },

    ensureInitialized(block) {
        if (block.sourceKey === undefined) block.sourceKey = '';
        if (block.limit === undefined || block.limit === null) block.limit = DEFAULT_LIMIT;
        if (!block.orderBy) block.orderBy = DEFAULT_ORDER_BY;
        if (!block.orderDirection) block.orderDirection = DEFAULT_ORDER_DIRECTION;
        if (!block.filters || typeof block.filters !== 'object') block.filters = {};
        if (block.view === undefined) block.view = '';
        return block;
    },

    cleanup(block) {
        delete block.sourceKey;
        delete block.limit;
        delete block.orderBy;
        delete block.orderDirection;
        delete block.filters;
        delete block.view;
        return block;
    },

    focusable: false,

    focus(element) {
        if (!element) return false;
        if (element.scrollIntoView) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return true;
    },
};
