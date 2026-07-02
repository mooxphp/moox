import { BLOCK_TYPES } from '../../components/block-types.js';

function decodeEntities(text) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
}

function parseJsonCandidates(value) {
    const trimmed = value.trim();
    const decoded = decodeEntities(trimmed).trim();

    return decoded !== trimmed ? [trimmed, decoded] : [trimmed];
}

function parseBooleanAttribute(rootElement, datasetKey, attributeName, defaultValue) {
    if (!rootElement) {
        return defaultValue;
    }

    const parent = rootElement.parentElement;
    const raw =
        rootElement.dataset?.[datasetKey] ??
        rootElement.getAttribute(attributeName) ??
        parent?.dataset?.[datasetKey] ??
        parent?.getAttribute(attributeName);

    if (raw === undefined || raw === null || raw === '') {
        return defaultValue;
    }

    const normalized = String(raw).trim().toLowerCase();

    return normalized !== '0' && normalized !== 'false' && normalized !== 'no' && normalized !== 'off';
}

export function normalizeInitialJsonText(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const jsonText = value.trim();
    if (!jsonText) {
        return null;
    }

    const candidates = parseJsonCandidates(jsonText);

    for (const candidate of candidates) {
        try {
            const parsed = JSON.parse(candidate);

            if (Array.isArray(parsed)) {
                return JSON.stringify(parsed);
            }

            if (typeof parsed === 'string') {
                const parsedNested = JSON.parse(parsed);
                if (Array.isArray(parsedNested)) {
                    return JSON.stringify(parsedNested);
                }
            }
        } catch (_error) {
            // Candidate is not valid JSON, continue with next candidate.
        }
    }

    return null;
}

function resolvePositiveBlockTypes(rootElement) {
    const parent = rootElement?.parentElement;
    if (!parent) {
        return BLOCK_TYPES;
    }

    let json = parent.dataset?.positiveBlock;
    if (!json || typeof json !== 'string') {
        json = parent.getAttribute('data-positive-block');
    }

    if (!json || typeof json !== 'string') {
        return BLOCK_TYPES;
    }

    const candidates = parseJsonCandidates(json);

    try {
        let parsed = null;
        for (const candidate of candidates) {
            try {
                parsed = JSON.parse(candidate);
                break;
            } catch (_e) {
                // try next candidate
            }
        }

        if (!parsed || !Array.isArray(parsed) || parsed.length === 0) {
            return BLOCK_TYPES;
        }

        const filteredBlockTypes = {};
        for (const blockType of parsed) {
            if (typeof blockType === 'string' && BLOCK_TYPES[blockType]) {
                filteredBlockTypes[blockType] = BLOCK_TYPES[blockType];
            }
        }

        return Object.keys(filteredBlockTypes).length > 0 ? filteredBlockTypes : BLOCK_TYPES;
    } catch (_error) {
        return BLOCK_TYPES;
    }
}

function resolveNegativeBlockKeys(rootElement) {
    const parent = rootElement?.parentElement;
    if (!parent) {
        return null;
    }

    let json = parent.dataset?.negativeBlock;
    if (!json || typeof json !== 'string') {
        json = parent.getAttribute('data-negative-block');
    }

    if (!json || typeof json !== 'string') {
        return null;
    }

    const candidates = parseJsonCandidates(json);

    for (const candidate of candidates) {
        try {
            const parsed = JSON.parse(candidate);
            if (Array.isArray(parsed) && parsed.length > 0) {
                return parsed.filter((x) => typeof x === 'string' && x !== '');
            }
        } catch (_e) {
            // try next candidate
        }
    }

    return null;
}

/**
 * Whitelist (data-positive-block) then blacklist (data-negative-block) on childBlockTypes.
 */
export function resolveChildBlockTypes(rootElement) {
    const base = resolvePositiveBlockTypes(rootElement);
    const exclude = resolveNegativeBlockKeys(rootElement);
    if (!exclude || exclude.length === 0) {
        return base;
    }

    const next = { ...base };
    for (const key of exclude) {
        delete next[key];
    }

    if (Object.keys(next).length === 0) {
        return base;
    }

    return next;
}

export function resolveThemeTemplatesEnabled(rootElement) {
    return parseBooleanAttribute(rootElement, 'mooxThemeTemplates', 'data-moox-theme-templates', true);
}

export function resolveDeveloperJsonEnabled(rootElement) {
    return parseBooleanAttribute(rootElement, 'developerJson', 'data-developer-json', false);
}

export function resolveAddComponentsEnabled(rootElement) {
    return parseBooleanAttribute(rootElement, 'addComponents', 'data-add-components', true);
}

export function resolveJsonImportEnabled(rootElement) {
    return parseBooleanAttribute(rootElement, 'jsonImport', 'data-json-import', false);
}

function readStringAttribute(rootElement, datasetKey, attributeName) {
    if (!rootElement) {
        return null;
    }

    const parent = rootElement.parentElement;
    const raw =
        rootElement.dataset?.[datasetKey] ??
        rootElement.getAttribute(attributeName) ??
        parent?.dataset?.[datasetKey] ??
        parent?.getAttribute(attributeName);

    if (raw === undefined || raw === null) {
        return null;
    }

    const normalized = String(raw).trim();

    return normalized !== '' ? normalized : null;
}

export function resolveMediaLibraryApiUrl(rootElement) {
    return readStringAttribute(rootElement, 'mediaLibraryApiUrl', 'data-media-library-api-url') ?? '/api/media';
}

export function resolveMediaLibraryCollection(rootElement) {
    return readStringAttribute(rootElement, 'mediaLibraryCollection', 'data-media-library-collection');
}

export function resolveMediaUsableType(rootElement) {
    return readStringAttribute(rootElement, 'mediaUsableType', 'data-media-usable-type') ?? '';
}

export function resolveMediaUsableId(rootElement) {
    const value = readStringAttribute(rootElement, 'mediaUsableId', 'data-media-usable-id') ?? '';
    const parsed = Number(value);

    if (Number.isInteger(parsed) && parsed > 0) {
        return String(parsed);
    }

    return '';
}

export function resolveMediaUploadLanguage(rootElement) {
    return readStringAttribute(rootElement, 'mediaUploadLanguage', 'data-media-upload-language');
}

export function resolveEditorAssetVersion(rootElement) {
    return readStringAttribute(rootElement, 'editorAssetVersion', 'data-editor-asset-version');
}
