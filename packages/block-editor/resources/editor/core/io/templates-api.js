const DEFAULT_TEMPLATES_API_URL = '/api/editor/v1/templates';

function normalizeTemplatesApiUrl(url) {
    if (typeof url !== 'string') {
        return null;
    }

    const normalized = url.trim();
    if (!normalized) {
        return null;
    }

    return normalized.endsWith('/') ? normalized.slice(0, -1) : normalized;
}

function resolveTemplatesApiUrl() {
    const fromWindow = normalizeTemplatesApiUrl(window?.mooxEditorTemplatesApiUrl);
    if (fromWindow) {
        return fromWindow;
    }

    const rootElement = document.querySelector('[data-templates-api-url]');
    const fromDataAttribute = normalizeTemplatesApiUrl(
        rootElement?.dataset?.templatesApiUrl ?? rootElement?.getAttribute('data-templates-api-url')
    );

    return fromDataAttribute ?? DEFAULT_TEMPLATES_API_URL;
}

const TEMPLATES_API_URL = resolveTemplatesApiUrl();
let templatesApiTemporarilyDisabled = false;

function buildHttpError(message, status) {
    const error = new Error(message);
    error.status = status;
    return error;
}

export function normalizeTemplate(template) {
    const content = Array.isArray(template?.content)
        ? template.content
        : (Array.isArray(template?.data) ? template.data : []);

    return {
        id: template?.id ?? null,
        name: template?.name ?? 'Unbenanntes Template',
        slug: template?.slug ?? null,
        filename: template?.slug ? `${template.slug}.json` : (template?.filename ?? null),
        data: content,
        createdAt: template?.created_at ?? template?.createdAt ?? new Date().toISOString(),
        updatedAt: template?.updated_at ?? template?.updatedAt ?? new Date().toISOString()
    };
}

function extractTemplatesFromApiPayload(payload) {
    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

export async function apiRequest(path = '', options = {}) {
    if (templatesApiTemporarilyDisabled) {
        throw buildHttpError('Templates API is temporarily disabled.', 401);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch(`${TEMPLATES_API_URL}${path}`, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(options.headers || {})
        },
        ...options
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
            templatesApiTemporarilyDisabled = true;
        }

        throw buildHttpError(message, response.status);
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}

export async function fetchTemplatesFromApi() {
    const perPage = 100;
    const firstPayload = await apiRequest(`?per_page=${perPage}&sort=id&direction=desc`);
    const firstTemplates = extractTemplatesFromApiPayload(firstPayload);

    if (!Array.isArray(firstPayload?.data)) {
        return firstTemplates;
    }

    const totalPages = Number(firstPayload?.last_page ?? 1);
    if (!Number.isFinite(totalPages) || totalPages <= 1) {
        return firstTemplates;
    }

    const allTemplates = [...firstTemplates];
    for (let page = 2; page <= totalPages; page += 1) {
        // eslint-disable-next-line no-await-in-loop
        const pagePayload = await apiRequest(`?per_page=${perPage}&sort=id&direction=desc&page=${page}`);
        allTemplates.push(...extractTemplatesFromApiPayload(pagePayload));
    }

    return allTemplates;
}
