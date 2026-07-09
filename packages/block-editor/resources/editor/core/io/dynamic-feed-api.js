const DEFAULT_DYNAMIC_FEEDS_API_URL = '/api/editor/v1/dynamic-feeds';

function normalizeDynamicFeedsApiUrl(url) {
    if (typeof url !== 'string') {
        return null;
    }

    const normalized = url.trim().replace(/\/+$/, '');
    if (!normalized) {
        return null;
    }

    return normalized.replace(/\/sources$/i, '');
}

function resolveDynamicFeedsApiUrl() {
    const fromWindow = normalizeDynamicFeedsApiUrl(window?.mooxEditorDynamicFeedsApiUrl);
    if (fromWindow) {
        return fromWindow;
    }

    const rootElement = document.querySelector('[data-dynamic-feeds-api-url]');
    const fromDataAttribute = normalizeDynamicFeedsApiUrl(
        rootElement?.dataset?.dynamicFeedsApiUrl ?? rootElement?.getAttribute('data-dynamic-feeds-api-url')
    );

    return fromDataAttribute ?? DEFAULT_DYNAMIC_FEEDS_API_URL;
}

const DYNAMIC_FEEDS_API_URL = resolveDynamicFeedsApiUrl();
let dynamicFeedsApiTemporarilyDisabled = false;

function buildHttpError(message, status) {
    const error = new Error(message);
    error.status = status;
    return error;
}

function extractData(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

export async function dynamicFeedApiRequest(path = '', options = {}) {
    if (dynamicFeedsApiTemporarilyDisabled) {
        throw buildHttpError('Dynamic feeds API is temporarily disabled.', 401);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;

    const response = await fetch(`${DYNAMIC_FEEDS_API_URL}${normalizedPath}`, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(options.headers || {}),
        },
        ...options,
    });

    if (!response.ok) {
        let message = `HTTP ${response.status}`;
        try {
            const payload = await response.json();
            message = payload?.message || payload?.error || message;
        } catch (_error) {
            // Ignore JSON parsing errors and use default message.
        }

        if (response.status === 401 || response.status === 403) {
            dynamicFeedsApiTemporarilyDisabled = true;
        }

        throw buildHttpError(message, response.status);
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}

export async function fetchDynamicFeedSources() {
    const payload = await dynamicFeedApiRequest('/sources');
    return extractData(payload);
}

export async function fetchDynamicFeedViews(sourceKey) {
    const payload = await dynamicFeedApiRequest(`/sources/${encodeURIComponent(sourceKey)}/views`);
    return extractData(payload);
}

export async function fetchDynamicFeedFilterOptions(sourceKey, filterKey, locale = '') {
    const params = new URLSearchParams();
    if (locale) {
        params.set('lang', locale);
    }

    const query = params.toString();
    const path = `/sources/${encodeURIComponent(sourceKey)}/filter-options/${encodeURIComponent(filterKey)}${query ? `?${query}` : ''}`;
    const payload = await dynamicFeedApiRequest(path);
    return extractData(payload);
}

export async function fetchDynamicFeedPreview(config = {}) {
    const params = new URLSearchParams();

    if (config.sourceKey) {
        params.set('sourceKey', config.sourceKey);
    }
    if (config.limit !== undefined && config.limit !== null && config.limit !== '') {
        params.set('limit', String(config.limit));
    }
    if (config.orderBy) {
        params.set('orderBy', config.orderBy);
    }
    if (config.orderDirection) {
        params.set('orderDirection', config.orderDirection);
    }
    if (config.view) {
        params.set('view', config.view);
    }
    if (config.locale) {
        params.set('lang', config.locale);
    }

    if (config.filters && typeof config.filters === 'object') {
        Object.entries(config.filters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params.set(`filters[${key}]`, String(value));
            }
        });
    }

    const query = params.toString();
    const payload = await dynamicFeedApiRequest(`/preview${query ? `?${query}` : ''}`);
    return {
        locale: payload?.locale ?? config.locale ?? '',
        count: Number(payload?.count ?? 0),
        items: Array.isArray(payload?.items) ? payload.items : [],
    };
}
