// Format Utilities
/** Erzeugt eine eindeutige ID (Timestamp + optionaler Offset für Mehrfachaufrufe in derselben ms). */
export function generateId(blockIdCounter) {
    const offset = Number(blockIdCounter) || 0;
    return String(Date.now() + offset);
}

export function ensureMatchingPageProtocol(url) {
    if (typeof url !== 'string') {
        return '';
    }

    const normalized = url.trim();

    if (
        normalized === ''
        || typeof window === 'undefined'
        || window.location?.protocol !== 'https:'
        || !normalized.startsWith('http://')
    ) {
        return normalized;
    }

    return normalized.replace(/^http:/, 'https:');
}
