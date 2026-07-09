/**
 * Dynamic Feed Block Component
 * Laedt Entity-Daten zur Laufzeit ueber die Editor-API.
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
        emptyMessage: '',
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
        const blockId = data.id || '';

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
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border border-violet-500/30 bg-violet-500/12 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-violet-200">
                                    Dynamischer Inhalt
                                </span>
                                <span
                                    x-show="${scope}.sourceKey"
                                    class="inline-flex items-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[11px] font-medium text-emerald-200"
                                >
                                    Live Vorschau aktiv
                                </span>
                                <span
                                    x-show="!${scope}.sourceKey"
                                    class="inline-flex items-center rounded-full border border-amber-400/20 bg-amber-400/10 px-2.5 py-1 text-[11px] font-medium text-amber-200"
                                >
                                    Quelle fehlt
                                </span>
                            </div>
                            <p class="mt-3 text-base font-semibold text-white" x-text="getDynamicFeedSourceMeta(${scope})?.label || 'Quelle waehlen'"></p>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-300">
                                Quelle, Filter und Darstellung direkt im Block konfigurieren. Die Vorschau bleibt im selben visuellen System wie der Editor.
                            </p>
                        </div>
                        <div class="shrink-0">
                            <button
                                type="button"
                                class="inline-flex min-h-11 items-center rounded-full border border-slate-600 bg-slate-900/80 px-4 text-sm font-medium text-slate-100 transition hover:border-slate-500 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-500"
                                @click.stop="openSidebar(${scope}.id)"
                            >
                                Einstellungen
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]">
                        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Konfiguration</p>
                                    <p class="mt-1 text-sm text-slate-300">Steuert Quelle, Filter und Ausgabe des Feeds.</p>
                                </div>
                                <div class="hidden rounded-xl border border-slate-800 bg-slate-950/80 px-3 py-2 text-right sm:block">
                                    <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-500">Elemente</p>
                                    <p class="text-base font-semibold text-white" x-text="${scope}.limit || ${DEFAULT_LIMIT}"></p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Quelle
                                    </label>
                                    <div x-show="dynamicFeedSourcesLoading" class="space-y-2" aria-live="polite">
                                        <div class="h-12 animate-pulse rounded-xl border border-slate-800 bg-slate-800/80"></div>
                                    </div>
                                    <div
                                        x-show="!dynamicFeedSourcesLoading && dynamicFeedSourcesError"
                                        class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100"
                                        x-text="dynamicFeedSourcesError"
                                    ></div>
                                    <div
                                        x-show="!dynamicFeedSourcesLoading && !dynamicFeedSourcesError && dynamicFeedSources.length === 0"
                                        class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100"
                                    >
                                        Keine Quellen verfuegbar.
                                    </div>
                                    <select
                                        x-show="!dynamicFeedSourcesLoading && dynamicFeedSources.length > 0"
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
                                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400" x-text="filter.label || filter.key"></label>
                                        <div x-show="isDynamicFeedFilterOptionsLoading(${scope}.sourceKey, filter.key)" class="h-12 animate-pulse rounded-xl border border-slate-800 bg-slate-800/80"></div>
                                        <select
                                            x-show="!isDynamicFeedFilterOptionsLoading(${scope}.sourceKey, filter.key)"
                                            @click.stop
                                            :value="(${scope}.filters && ${scope}.filters[filter.key] !== undefined) ? ${scope}.filters[filter.key] : ''"
                                            @change="updateDynamicFeedFilter(${scope}.id, filter.key, $event.target.value)"
                                            class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/25"
                                        >
                                            <option value="" x-text="filter.nullable === false ? 'Bitte waehlen…' : 'Alle'"></option>
                                            <template x-for="option in getDynamicFeedFilterOptions(${scope}.sourceKey, filter.key)" :key="option.value">
                                                <option :value="option.value" :selected="${scope}.filters && String(${scope}.filters[filter.key]) === String(option.value)" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>

                                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
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
                                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm font-medium text-white outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/25"
                                    />
                                    <p class="mt-2 text-xs leading-5 text-slate-400">Empfohlen fuer Vorschau und Editor: 3 bis 6 Eintraege.</p>
                                </div>

                                <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Darstellung
                                    </label>
                                    <select
                                        @click.stop
                                        :value="${scope}.view || ''"
                                        @change="updateDynamicFeedView(${scope}.id, $event.target.value)"
                                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/25"
                                    >
                                        <option value="">Standard</option>
                                        <template x-for="view in getDynamicFeedViews(${scope})" :key="view.key">
                                            <option :value="view.key" :selected="${scope}.view === view.key" x-text="view.label"></option>
                                        </template>
                                    </select>
                                    <p class="mt-2 text-xs leading-5 text-slate-400">Waehlt die visuelle Form der Ausgabe, ohne die Query zu aendern.</p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Status</p>
                                    <p class="mt-1 text-sm text-slate-300">Schneller Ueberblick ueber die aktive Feed-Konfiguration.</p>
                                </div>
                                <div class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs font-medium text-slate-200" x-text="${scope}.view || 'Standard'"></div>
                            </div>

                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Quelle</dt>
                                    <dd class="mt-2 text-sm font-medium text-white" x-text="getDynamicFeedSourceMeta(${scope})?.label || 'Nicht gesetzt'"></dd>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Anzahl</dt>
                                    <dd class="mt-2 text-sm font-medium text-white" x-text="${scope}.limit || ${DEFAULT_LIMIT}"></dd>
                                </div>
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4 sm:col-span-2">
                                    <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Filter</dt>
                                    <dd class="mt-2 flex flex-wrap gap-2">
                                        <template x-if="Object.keys(${scope}.filters || {}).length === 0">
                                            <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-xs text-slate-300">Keine Filter aktiv</span>
                                        </template>
                                        <template x-for="[filterKey, filterValue] in Object.entries(${scope}.filters || {})" :key="filterKey">
                                            <span class="inline-flex items-center rounded-full border border-cyan-500/20 bg-cyan-500/10 px-2.5 py-1 text-xs font-medium text-cyan-100">
                                                <span x-text="filterKey"></span>
                                                <span class="mx-1 text-cyan-300">:</span>
                                                <span x-text="filterValue"></span>
                                            </span>
                                        </template>
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Vorschau</p>
                                <p class="mt-1 text-sm text-slate-300">
                                    <span x-text="getDynamicFeedPreviewState('${blockId}').count || 0"></span>
                                    Eintraege geladen
                                </p>
                            </div>
                            <div class="hidden rounded-xl border border-slate-800 bg-slate-950/80 px-3 py-2 text-right sm:block">
                                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-slate-500">Ansicht</p>
                                <p class="text-sm font-semibold text-white" x-text="${scope}.view || 'Standard'"></p>
                            </div>
                        </div>

                        <div x-show="${scope}.sourceKey && isDynamicFeedPreviewLoading('${blockId}')" class="space-y-3" aria-live="polite">
                            <template x-for="index in 3" :key="index">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 p-4">
                                    <div class="animate-pulse space-y-3">
                                        <div class="h-4 w-1/3 rounded bg-slate-800"></div>
                                        <div class="h-3 w-full rounded bg-slate-800"></div>
                                        <div class="h-3 w-4/5 rounded bg-slate-800"></div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div
                            x-show="${scope}.sourceKey && !isDynamicFeedPreviewLoading('${blockId}') && getDynamicFeedPreviewError('${blockId}')"
                            class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100"
                            x-text="getDynamicFeedPreviewError('${blockId}')"
                        ></div>

                        <div x-show="${scope}.sourceKey && !isDynamicFeedPreviewLoading('${blockId}') && !getDynamicFeedPreviewError('${blockId}')">
                            <template x-if="(getDynamicFeedPreviewState('${blockId}').items || []).length === 0">
                                <p class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/80 p-5 text-sm leading-6 text-slate-300">
                                    <span x-text="(${scope}.emptyMessage || '').trim() || 'Keine Eintraege fuer die aktuelle Konfiguration.'"></span>
                                </p>
                            </template>

                            <ul class="space-y-3" x-show="(getDynamicFeedPreviewState('${blockId}').items || []).length > 0">
                                <template x-for="item in getDynamicFeedPreviewState('${blockId}').items" :key="item.id || item.title">
                                    <li class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/90 shadow-sm">
                                        <div class="flex flex-col gap-4 p-4 sm:flex-row">
                                            <div
                                                x-show="item.image_url"
                                                class="h-40 overflow-hidden rounded-xl border border-slate-800 bg-slate-900 sm:h-24 sm:w-32 sm:shrink-0"
                                            >
                                                <img
                                                    :src="item.image_url"
                                                    :alt="item.title || 'Feed Bild'"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                >
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="text-base font-semibold leading-6 text-white" x-text="item.title || 'Ohne Titel'"></p>
                                                        <div class="mt-2 flex flex-wrap gap-2" x-show="Array.isArray(item.categories) && item.categories.length > 0">
                                                            <template x-for="category in (item.categories || []).slice(0, 3)" :key="category">
                                                                <span class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-slate-200" x-text="category"></span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    <span
                                                        x-show="item.published_at"
                                                        class="inline-flex shrink-0 items-center rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[11px] font-medium text-slate-200"
                                                        x-text="item.published_at ? new Date(item.published_at).toLocaleDateString() : ''"
                                                    ></span>
                                                </div>

                                                <p
                                                    class="mt-3 text-sm leading-6 text-slate-300"
                                                    x-show="item.excerpt_plain || item.description_plain"
                                                    x-text="(item.excerpt_plain || item.description_plain || '').slice(0, 180)"
                                                ></p>

                                                <a
                                                    x-show="item.permalink"
                                                    :href="item.permalink"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="mt-3 inline-flex min-h-11 items-center rounded-full border border-violet-500/25 bg-violet-500/10 px-4 text-sm font-medium text-violet-100 transition hover:border-violet-400/40 hover:bg-violet-500/15 focus:outline-none focus:ring-2 focus:ring-violet-500"
                                                >
                                                    Beitrag ansehen
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        `;
    },

    getSettingsHTML(block) {
        const blockId = block.id || '';

        return `
            <div class="space-y-4" x-init="$nextTick(() => ensureDynamicFeedBlockReady(block))">
                <div class="rounded-xl border border-slate-700 bg-slate-900/80 p-4 text-sm text-slate-200">
                    Die Hauptkonfiguration passiert direkt im Block. Hier findest du nur den leeren Zustand.
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200" for="dynamic-feed-empty-${blockId}">
                        Leerer Zustand
                    </label>
                    <input
                        id="dynamic-feed-empty-${blockId}"
                        type="text"
                        :value="block.emptyMessage || ''"
                        @input="updateDynamicFeedEmptyMessage(block.id, $event.target.value)"
                        placeholder="Optionaler Hinweis, wenn keine Eintraege gefunden werden"
                        class="min-h-12 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30"
                    />
                </div>
            </div>
        `;
    },

    initialize(block) {
        if (!block.sourceKey) block.sourceKey = '';
        if (!block.limit) block.limit = DEFAULT_LIMIT;
        if (!block.orderBy) block.orderBy = DEFAULT_ORDER_BY;
        if (!block.orderDirection) block.orderDirection = DEFAULT_ORDER_DIRECTION;
        if (!block.filters || typeof block.filters !== 'object') block.filters = {};
        if (!block.view) block.view = '';
        if (!block.emptyMessage) block.emptyMessage = '';
        return block;
    },

    ensureInitialized(block) {
        if (block.sourceKey === undefined) block.sourceKey = '';
        if (block.limit === undefined || block.limit === null) block.limit = DEFAULT_LIMIT;
        if (!block.orderBy) block.orderBy = DEFAULT_ORDER_BY;
        if (!block.orderDirection) block.orderDirection = DEFAULT_ORDER_DIRECTION;
        if (!block.filters || typeof block.filters !== 'object') block.filters = {};
        if (block.view === undefined) block.view = '';
        if (block.emptyMessage === undefined) block.emptyMessage = '';
        return block;
    },

    cleanup(block) {
        delete block.sourceKey;
        delete block.limit;
        delete block.orderBy;
        delete block.orderDirection;
        delete block.filters;
        delete block.view;
        delete block.emptyMessage;
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
